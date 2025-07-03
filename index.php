<?php

require_once 'vendor/autoload.php';
require_once 'load_env.php';
loadEnv(__DIR__ . '/.env');

session_start();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIR_URL']);
$client->addScope('email');
$client->addScope('profile');

$login_url = $client->createAuthUrl();

?>

<!DOCTYPE HTML>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SPREAD - Olvass gyorsabban</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.6/dist/htmx.min.js"
        integrity="sha384-Akqfrbj/HpNVo8k11SXBb6TlBWmXXlYQrCSqEWmyKJe+hDm3Z/B2WVG4smwBkRVm"
        crossorigin="anonymous"></script>



    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="snack.css">
    <link rel="stylesheet" href="display.css">
    <style>
    </style>
</head>

<body>

    <nav class="navbar navbar-dark bg-info-subtle px-3">
        <div class="container">
            <span class="navbar-brand mb-0 h1">SPREAD</span>

            <?php


            $picture = $_SESSION['user_picture'];

            if (isset($_SESSION['access_token'])) {
                echo <<<HTML
                    <div id="dropdown" class="dropdown">
                        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle"
                            id="dropdownProfile" data-bs-toggle="dropdown" aria-expanded="false">
                            <img width='auto' height="30px" src="$picture" alt="Profile" onerror="this.onerror=null;this.src='/ass/profile-circle.svg';"
                                class="rounded-circle">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfile">
                            <li><a onclick="openBookmarks();" class="dropdown-item" href="#">Könyvjelzők</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a hx-get="/backend/file.php?logout=1" hx-target="#dropdown" hx-swap="innerHTML"
                              class="dropdown-item" href="#">Kijelentkezés</a></li>
                        </ul>
                    </div>
                HTML;
            } else {
                echo <<<HTML
                    <div class="dropdown">
                        <a href="$login_url">
                            <img src="/ass/log-in.svg">
                        </a>
                    </div>
                HTML;
            }

            ?>


        </div>

        <form hidden id="bookmarkForm" hx-post="/backend/file.php?saveBookmark=1" hx-target="#snackbar"
            hx-swap="innerHTML">
            <input id="form_wordIndex" type="hidden" name="form_wordIndex">
            <input id="form_speed" type="hidden" name="form_speed">
            <input id="form_title" type="hidden" name="form_title">
        </form>



    </nav>
    <div class="container">
        <h1>Spread - Olvass Gyorsabban</h1>
        <div id="authorTitle" class="info">
            Nincs könyv megnyitva
        </div>
        <div id="display" class="display-area">
            Indításra kész...
        </div>

        <div class="controlls">
            <button id="playBtn" class="control-btn">
                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg" color="#ffffff">
                    <path
                        d="M6.90588 4.53682C6.50592 4.2998 6 4.58808 6 5.05299V18.947C6 19.4119 6.50592 19.7002 6.90588 19.4632L18.629 12.5162C19.0211 12.2838 19.0211 11.7162 18.629 11.4838L6.90588 4.53682Z"
                        stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
            <button id="pauseBtn" class="control-btn">
                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg" color="#ffffff">
                    <path
                        d="M6 18.4V5.6C6 5.26863 6.26863 5 6.6 5H9.4C9.73137 5 10 5.26863 10 5.6V18.4C10 18.7314 9.73137 19 9.4 19H6.6C6.26863 19 6 18.7314 6 18.4Z"
                        stroke="#ffffff" stroke-width="1.5"></path>
                    <path
                        d="M14 18.4V5.6C14 5.26863 14.2686 5 14.6 5H17.4C17.7314 5 18 5.26863 18 5.6V18.4C18 18.7314 17.7314 19 17.4 19H14.6C14.2686 19 14 18.7314 14 18.4Z"
                        stroke="#ffffff" stroke-width="1.5"></path>
                </svg>
            </button>
            <button id="resetBtn" class="control-btn">
                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg" color="#ffffff">
                    <path
                        d="M6.67742 20.5673C2.53141 18.0212 0.758026 12.7584 2.71678 8.1439C4.87472 3.0601 10.7453 0.68822 15.8291 2.84617C20.9129 5.00412 23.2848 10.8747 21.1269 15.9585C20.2837 17.945 18.8736 19.5174 17.1651 20.5673"
                        stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M17 16V20.4C17 20.7314 17.2686 21 17.6 21H22" stroke="#ffffff" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M12 22.01L12.01 21.9989" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round"></path>
                </svg>
            </button>
            <button onclick="bookmarkFormHandler();" id="bookmark" class="control-btn">
                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg" color="#ffffff">
                    <path
                        d="M5 21V5C5 3.89543 5.89543 3 7 3H17C18.1046 3 19 3.89543 19 5V21L13.0815 17.1953C12.4227 16.7717 11.5773 16.7717 10.9185 17.1953L5 21Z"
                        stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        </div>

        <div id="status" class="status">
            Pozíció: 0 | Sebesség: 120 WPM | Státusz: Vége
        </div>

        <div class="status">
            <div class="speed-control">
                <label class="speedLabel" for="speed">Speed (WPM):</label>
                <input type="number" id="speed" value="120" min="1" max="1000">
            </div>
        </div>

        <form id="fileForm" hx-post="/backend/file.php?upload=1" hx-target="#snackbar" hx-swap="innerHTML"
            hx-encoding="multipart/form-data" class="fileForm">
            <label id="fileLabel" for="fileInput" class="button"
                style="background: rgb(0, 43, 91); color: white; border-radius: 4px; padding: 2px 10px; margin: 0px 2px; cursor: pointer; width: 23ch;">
                + Könyv Kiválasztása
            </label>
            <input hidden class="fileInput" id="fileInput" name="fileInput" type="file">
            <span id="filename" class="filename" style="justify-self: end;"></span>
        </form>

        <a class="text-decoration-none" data-bs-toggle="collapse" href="#collapseExample" role="button"
            aria-expanded="false" aria-controls="collapseExample">
            <div class="bmHeaderContainer">
                <span class="bmHeading">Könyvjelzők</span>
                <span style="justify-self: end;">
                    <svg width="24px" height="24px" viewBox="0 0 24 24" stroke-width="1.5" fill="none"
                        xmlns="http://www.w3.org/2000/svg" color="#ffffff">
                        <path
                            d="M20 12V5.74853C20 5.5894 19.9368 5.43679 19.8243 5.32426L16.6757 2.17574C16.5632 2.06321 16.4106 2 16.2515 2H4.6C4.26863 2 4 2.26863 4 2.6V21.4C4 21.7314 4.26863 22 4.6 22H11"
                            stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M8 10H16M8 6H12M8 14H11" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"></path>
                        <path d="M20.5 20.5L22 22" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"></path>
                        <path
                            d="M15 18C15 19.6569 16.3431 21 18 21C18.8299 21 19.581 20.663 20.1241 20.1185C20.6654 19.5758 21 18.827 21 18C21 16.3431 19.6569 15 18 15C16.3431 15 15 16.3431 15 18Z"
                            stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M16 2V5.4C16 5.73137 16.2686 6 16.6 6H20" stroke="#ffffff" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </span>
            </div>
        </a>

        <div class="collapse" id="collapseExample">
            <div id="bookmarks-container">Nincsenek Könyvjelzők</div>
        </div>

        <div class="snackbar" id="snackbar"></div>
        <script id="wordsArray" type="application/json"></script>
        </script>
        <h2>Mi is ez?</h2>
        <p>A SPREAD célja, hogy epub formátumú könyveket a lehető leggyorsabban el lehessen olvasni.</p>
        <h4 id="mi-az-rsvp-">Mi az RSVP?</h4>
        <p>A Rapid Serial Visual Presentation (RSVP) egy olyan olvasási technika, amely során a szöveg szavait vagy
            szócsoportjait egymás után, gyors egymásutánban, egy fix helyen jelenítik meg a képernyőn. Ez a módszer
            különösen népszerű a gyorsolvasó alkalmazásokban és digitális eszközökön.</p>
        <h4 id="az-rsvp-olvas-s-f-el-nyei">Az RSVP olvasás fő előnyei</h4>
        <ul>
            <li><strong>Jelentősen növelhető az olvasási sebesség</strong>
                Az RSVP technika lehetővé teszi, hogy a szavak gyors egymásutánban jelenjenek meg, így a szemnek nem
                kell folyamatosan mozognia a sorok között. Ezáltal az olvasási sebesség akár többszörösére is növelhető
                a hagyományos olvasáshoz képest<a
                    href="https://www.tutkit.com/hu/szoeveges-oktatoprogramok/3769-sebessegolvasas-kezdok-szamara-strategia"><sup>1</sup></a> <a
                    href="https://readima.com/boost-your-reading-speed-and-comprehension-with-this-simple-trick-rsvp/"><sup>3</sup></a>.
            </li>
            <li><strong>Fókuszált figyelem és jobb koncentráció</strong>
                Mivel a szavak egyetlen pontra koncentrálódnak, a figyelem kevésbé oszlik meg, így javulhat a
                koncentráció és csökken a zavaró tényezők hatása<a
                    href="https://lexiq.hu/rsvp"><sup>4</sup></a>.
            </li>
            <li><strong>Csökken a visszaugrások és újraolvasások száma</strong>
                Az RSVP módszer kizárja a visszaugrásokat (regressziókat), vagyis az olvasó nem tud visszamenni korábbi
                szavakhoz, ami segíthet a folyamatos előrehaladásban és a gyorsabb szövegfeldolgozásban<a
                    href="https://nottingham-repository.worktribe.com/OutputFile/765231"><sup>5</sup></a>.
            </li>
            <li><strong>Segíthet a szövegértésben</strong>
                A technika elősegítheti, hogy az olvasó minden szót feldolgozzon, mivel nincs lehetőség a szavak
                átugrására vagy kihagyására. Ez különösen hasznos lehet azok számára, akik hajlamosak a szöveg
                átfutására vagy kihagyására<a
                    href="https://readima.com/boost-your-reading-speed-and-comprehension-with-this-simple-trick-rsvp/"><sup>2</sup></a>.
            </li>
        </ul>
        <div style="text-align: center">⁂</div>
        <h2>A gyorsolvasó használata</h2>
        <ol>
            <li>Töltsd fel azt a könyvet amit el szeretnél olvasni .epub formátumban.</li>
            <li>Indítsd el a lejátszót, és állítsd be a sebességet, amivel kényelmesen tudod olvasni a szöveget.</li>
            <li>Amikor abbahagynád az olvasást, állítsd meg a lejátszót, ekkor a jelenleg olvasott könyv szövege, valamint az olvasási sebesség és az elolvasott szavak száma mentésre kerül a böngésződbe, így legközelebb ugyanott folytathatod, ahol abbahagytad.</li>
            <li>Ha más eszközön is szeretnéd az olvasást folytatni nincs más dolgod, mint bejelentkezni Google-fiókoddal, és megnyomni a könyvjelző gombot. Ekkor a rendszer elmenti az olvasott könyv címét és azt, hogy hol tartottál. A másik eszközön nyisd meg újra a könyvet, majd jelentkezz be. Ezután a könyvjelzők alatt megjelennek majd a mentett könyvjelzőid. Válaszd ki a megynitott könyvhöz megfelelőt, és folytasd onnan az olvasást ahol abbahagytad.</li>
        </ol>
        <p><span class="fw-bold text-danger">Figyelem!</span> A könyvek szövegét nem tároljuk szervereinken, így azt másik eszköz használatakor újra meg kell majd nyitni.</p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
        crossorigin="anonymous"></script>
    <script src="/js/reader.js"></script>
    <script src="/js/snack.js"></script>
    <script src="/js/bookmarks.js"></script>
    <script src="/js/file.js"></script>
    <script src="/js/main.js"></script>
</body>

</html>