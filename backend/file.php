<?php

require_once '../vendor/autoload.php';
require_once 'epub.php';
require_once '../acc/db.php';

require_once __DIR__ . '/../load_env.php';
loadEnv(__DIR__ . '/../.env');

session_start();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIR_URL']);
$client->addScope('email');
$client->addScope('profile');

$login_url = $client->createAuthUrl();


if (isset($_GET['logout'])) {
    session_destroy();

    header('HX-Trigger: logoutSuccess');

    echo <<<HTML
            <a href="$login_url">
                <img src="/ass/log-in.svg">
            </a>   
        HTML;
}

if (isset($_GET['upload'])) {
    $epubContent = null;
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['fileInput']['tmp_name'];
            $fileName = $_FILES['fileInput']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $finfo = null;
            $epubFile = null;

            try {
                // Check extension
                if ($fileExtension !== 'epub') {
                    $error = 'File extension is not .epub.';
                    header('HX-Trigger: snacktime');
                    echo "A file nem .epub formátumú.";
                } else {
                    // Check MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fileTmpPath);

                    // Typical MIME type for EPUB is application/epub+zip
                    if ($mimeType === 'application/epub+zip') {
                        // Read the file content into a variable


                        $epubFile = file_get_contents($fileTmpPath);

                        try {
                            $extractedBook = extractTextAndMetadataFromEpubSafely($epubFile);
                        } catch (Exception $e) {
                            echo "Error: " . $e->getMessage();
                            throw $e; // Re-throw to reach finally block
                        }

                        $extractedText = $extractedBook['text'];
                        $title = $extractedBook['metadata']['title'];
                        $author = $extractedBook['metadata']['author'];

                        $extractedText = str_replace(" -", "-", $extractedText);
                        $extractedText = str_replace("\n", " ", $extractedText);
                        $extractedText = str_replace("\t", " ", $extractedText);
                        $extractedText = str_replace("\r", " ", $extractedText);

                        $extractedText = explode(" ", $extractedText);

                        foreach ($extractedText as $key => $value) {
                            if (empty($value)) {
                                unset($extractedText[$key]);
                            }
                        }
                        $json = json_encode($extractedText, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                        $_SESSION['bookText'] = $json;
                        $_SESSION['author'] = $author;
                        $_SESSION['title'] = $title;

                        header('HX-Trigger-After-Settle: succ_ReadBook');


                    } else {
                        header('HX-Trigger: snacktime');
                        echo "A file nem .epub formátumú.";
                    }
                }
            } finally {
                // Cleanup resources
                if ($finfo) {
                    finfo_close($finfo);
                }

                // Delete temporary file if it exists
                if (file_exists($fileTmpPath)) {
                    unlink($fileTmpPath);
                }

                // Clear the variable memory
                if ($epubFile !== null) {
                    $epubFile = null;
                }
            }
        } else {
            $error = 'File upload error or no file uploaded.';
            header('HX-Trigger: snacktime');
            echo "Nincs megadott file / sikertelen feltöltés.";
        }
    }
}

if (isset($_GET['getbook'])) {
    echo $_SESSION['bookText'];
    $_SESSION['bookText'] = NULL;
}

if (isset($_GET['getTitleAuthor'])) {

    $data = array(
        "author" => $_SESSION['author'],
        "title" => $_SESSION['title']
    );

    $json = json_encode($data);


    echo $json;
}

if (isset($_GET['saveBookmark'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['access_token'])) {

        if (
            isset($_SESSION['google_id']) &&
            isset($_POST['form_wordIndex'], $_POST['form_speed'], $_POST['form_title'])
        ) {
            $google_id = $_SESSION['google_id'];
            $title = $_POST['form_title'];
            $wordIndex = intval($_POST['form_wordIndex']);
            $speed = floatval($_POST['form_speed']);

            try {
                $stmt = $pdo->prepare("
                INSERT INTO bookmarks (google_id, title, wordIndex, speed)
                VALUES (:google_id, :title, :wordIndex, :speed)
                ON DUPLICATE KEY UPDATE
                    google_id = VALUES(google_id),
                    wordIndex = VALUES(wordIndex),
                    speed = VALUES(speed)
            ");

                $stmt->execute([
                    ':google_id' => $google_id,
                    ':title' => $title,
                    ':wordIndex' => $wordIndex,
                    ':speed' => $speed,
                ]);

                header('HX-Trigger: bookmarkResponseHandler');
                echo "A könyvjelző sikeresen mentve/frissítve.";
            } catch (PDOException $e) {
                echo "Database error: " . $e->getMessage();
            }
        } else {
            echo "Hiányzó adatok.";
        }
    } else {
        header('HX-Trigger: snacktime');
        echo 'A könyvjelző mentéséhez jelentkezzen be!';
    }

}

if (isset($_GET['getBookmarks'])) {


    header('Content-Type: application/json');

    if (!isset($_SESSION['google_id'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'A könyvjelzők használatához jelentkezz be!']);
        exit;
    }

    $google_id = $_SESSION['google_id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE google_id = :google_id");
        $stmt->execute([':google_id' => $google_id]);
        $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($bookmarks);
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Adatbázis hiba!']);
        exit;
    }

}

if (isset($_GET['deleteBookmark'])) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('HX-Trigger: snacktime');
        echo "Nem megfelelő kérés tipus.";
        exit;
    }

    if (!isset($_SESSION['google_id'])) {
        http_response_code(401);
        header('HX-Trigger: snacktime');
        echo "A törléshez jelentkezz be!";
        exit;
    }

    if (empty($_POST['form_delete_title'])) {
        http_response_code(400);
        header('HX-Trigger: snacktime');
        echo "Hiányzó cím!";
        exit;
    }

    $google_id = $_SESSION['google_id'];
    $title = $_POST['form_delete_title'];

    try {
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE google_id = :google_id AND title = :title");
        $stmt->execute([
            ':google_id' => $google_id,
            ':title' => $title,
        ]);

        if ($stmt->rowCount() > 0) {
            header('HX-Trigger: bookmarkResponseHandler');
            echo "Könyvjelző sikeresen törölve";
        } else {
            header('HX-Trigger: bookmarkResponseHandler');
            echo "Ilyen könyvjelző nem létezik.";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        header('HX-Trigger: snacktime');
        echo "Adatbázis hiba";
    }
}