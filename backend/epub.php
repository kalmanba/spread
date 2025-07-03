<?php

// --- Security Configurations ---
define('MAX_UNCOMPRESSED_SIZE', 500 * 1024 * 1024); // 500 MB total uncompressed size limit
define('MAX_FILE_COUNT', 2000); // Maximum number of files in the archive
define('MAX_SINGLE_FILE_SIZE', 50 * 1024 * 1024); // 50 MB single file size limit

function extractTextAndMetadataFromEpubSafely(string $epubData): array
{
    // --- Input Validation ---
    if (empty($epubData)) {
        throw new Exception("EPUB data is empty");
    }

    // --- Create Secure Temporary Directory ---
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('epub_extract_', true);
    if (!mkdir($tempDir, 0700, true)) {
        throw new Exception("Failed to create temporary directory: " . $tempDir);
    }

    // Create temporary file for the EPUB data
    $tempEpubFile = $tempDir . DIRECTORY_SEPARATOR . 'temp_epub.epub';
    if (file_put_contents($tempEpubFile, $epubData) === false) {
        rmdir($tempDir);
        throw new Exception("Failed to write EPUB data to temporary file");
    }

    // Define cleanup function
    $cleanup = function () use ($tempDir) {
        if (is_dir($tempDir)) {
            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($rii as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($tempDir);
        }
    };

    // --- Open ZIP Archive ---
    $zip = new ZipArchive;
    if ($zip->open($tempEpubFile) !== TRUE) {
        $cleanup();
        throw new Exception("Failed to open EPUB data as ZIP archive.");
    }

    // --- Perform ZIP Security Checks Before Extraction ---
    $totalUncompressedSize = 0;
    if ($zip->numFiles > MAX_FILE_COUNT) {
        $zip->close();
        $cleanup();
        throw new Exception(sprintf("EPUB contains too many files (%d), exceeding limit of %d.", $zip->numFiles, MAX_FILE_COUNT));
    }

    $basePath = realpath($tempDir);
    if ($basePath === false) {
        $zip->close();
        $cleanup();
        throw new Exception("Failed to determine real path of temporary directory.");
    }

    // Iterate through each file entry in the ZIP archive
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if ($stat === false) {
            $zip->close();
            $cleanup();
            throw new Exception("Failed to get file stats from ZIP archive.");
        }

        $filename = $stat['name'];
        $uncompressedSize = $stat['size'];

        // --- Directory Traversal Check ---
        if (strpos($filename, '../') !== false || strpos($filename, '/') === 0 || strpos($filename, './') === 0) {
            $zip->close();
            $cleanup();
            throw new Exception("EPUB contains potentially malicious path: " . htmlspecialchars($filename));
        }

        // Check single file size limit
        if ($uncompressedSize > MAX_SINGLE_FILE_SIZE) {
            $zip->close();
            $cleanup();
            throw new Exception(sprintf("EPUB contains an excessively large file (%s bytes): %s", $uncompressedSize, htmlspecialchars($filename)));
        }

        $totalUncompressedSize += $uncompressedSize;
    }

    // --- Total Uncompressed Size Limit Check ---
    if ($totalUncompressedSize > MAX_UNCOMPRESSED_SIZE) {
        $zip->close();
        $cleanup();
        throw new Exception(sprintf("EPUB total uncompressed size (%s bytes) exceeds limit of %s bytes.", $totalUncompressedSize, MAX_UNCOMPRESSED_SIZE));
    }

    // --- Extract Files ---
    if (!$zip->extractTo($tempDir)) {
        $zip->close();
        $cleanup();
        throw new Exception("Failed to extract EPUB contents to temporary directory.");
    }
    $zip->close();

    // --- Find and Parse container.xml ---
    $containerXmlPath = $tempDir . DIRECTORY_SEPARATOR . 'META-INF' . DIRECTORY_SEPARATOR . 'container.xml';
    if (!file_exists($containerXmlPath)) {
        $cleanup();
        throw new Exception("META-INF/container.xml not found in EPUB.");
    }

    $containerXml = simplexml_load_file($containerXmlPath);
    if ($containerXml === false) {
        $cleanup();
        throw new Exception("Failed to parse META-INF/container.xml.");
    }

    $containerXml->registerXPathNamespace('c', 'urn:oasis:names:tc:opendocument:xmlns:container');
    $rootfiles = $containerXml->xpath('//c:rootfile');

    $opfPath = null;
    if ($rootfiles && isset($rootfiles[0]['full-path'])) {
        $opfPath = (string) $rootfiles[0]['full-path'];
    }

    if (!$opfPath || !file_exists($tempDir . DIRECTORY_SEPARATOR . $opfPath)) {
        $cleanup();
        throw new Exception("OPF file path not found in container.xml or OPF file not extracted.");
    }

    // --- Parse OPF File ---
    $opfFilePath = $tempDir . DIRECTORY_SEPARATOR . $opfPath;
    $opfXml = simplexml_load_file($opfFilePath);
    if ($opfXml === false) {
        $cleanup();
        throw new Exception("Failed to parse OPF file: " . $opfPath);
    }

    $opfXml->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
    $opfXml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

    // --- Extract Metadata ---
    $metadata = [
        'title' => '',
        'author' => ''
    ];

    // Extract title
    $titleNodes = $opfXml->xpath('//dc:title');
    if ($titleNodes && count($titleNodes) > 0) {
        $metadata['title'] = trim((string) $titleNodes[0]);
    }

    // Extract author (creator)
    $authorNodes = $opfXml->xpath('//dc:creator');
    if ($authorNodes && count($authorNodes) > 0) {
        $metadata['author'] = trim((string) $authorNodes[0]);
    }

    // Get manifest and spine
    $manifestItems = [];
    $items = $opfXml->xpath('//opf:manifest/opf:item');
    foreach ($items as $item) {
        $manifestItems[(string) $item['id']] = (string) $item['href'];
    }

    $spineItemRefs = [];
    $itemrefs = $opfXml->xpath('//opf:spine/opf:itemref');
    foreach ($itemrefs as $itemref) {
        $spineItemRefs[] = (string) $itemref['idref'];
    }

    if (empty($spineItemRefs)) {
        $cleanup();
        throw new Exception("No reading order (spine) found in OPF file.");
    }

    $opfDir = dirname($opfPath);

    // --- Extract Text from Content Files ---
    $bookText = '';
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);

    foreach ($spineItemRefs as $idref) {
        if (!isset($manifestItems[$idref])) {
            continue;
        }

        $htmlFilename = $manifestItems[$idref];
        $htmlFilePath = realpath($tempDir . DIRECTORY_SEPARATOR . ($opfDir === '.' || $opfDir === '' ? '' : $opfDir . DIRECTORY_SEPARATOR) . $htmlFilename);

        // Security check on resolved path
        if ($htmlFilePath === false || strpos($htmlFilePath, $basePath) !== 0) {
            error_log("Skipping potentially malicious HTML file path from OPF: " . htmlspecialchars($htmlFilename));
            continue;
        }

        if (!file_exists($htmlFilePath) || !is_readable($htmlFilePath)) {
            continue;
        }

        $htmlContent = file_get_contents($htmlFilePath);
        if ($htmlContent === false) {
            error_log("Failed to read HTML file: " . htmlspecialchars($htmlFilename));
            continue;
        }

        // Add encoding hint if not present
        if (!preg_match('/<\?xml[^>]+encoding=/', $htmlContent) && !preg_match('/<meta[^>]+charset=/', $htmlContent)) {
            $htmlContent = '<?xml encoding="UTF-8">' . $htmlContent;
        }

        @$dom->loadHTML($htmlContent);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body) {
            // Remove script and style tags
            $scriptTags = $body->getElementsByTagName('script');
            while ($scriptTags->length > 0) {
                $scriptTags->item(0)->parentNode->removeChild($scriptTags->item(0));
            }

            $styleTags = $body->getElementsByTagName('style');
            while ($styleTags->length > 0) {
                $styleTags->item(0)->parentNode->removeChild($styleTags->item(0));
            }

            $bookText .= $body->textContent . "\n\n";
        } else {
            $bookText .= $dom->textContent . "\n\n";
        }
    }

    // --- Final Cleanup ---
    $cleanup();
    libxml_use_internal_errors(false);

    // --- Return Results ---
    return [
        'text' => trim($bookText),
        'metadata' => $metadata
    ];
}
// if (isset($_SESSION['epubContent'])) {
//     $epubData = $_SESSION['epubContent'];
// } elseif (isset($_COOKIE['bookText'])) {
//     $epubData = $_COOKIE['bookText'];
// } else {
//     $epubData = "";
// }