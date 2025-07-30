# Spread -- Olvass gyorsabban

A spread egy az RSVP módszeren alapuló gyorsolvasó program, amely kifejezetten az .epub fájlok olvasására lett kifejlesztve.

## Funkciók
- Epub fájl feltöltése, a szöveg, szerző, cím megjelenítése. Ezen a adatok sütibe, illetve localstorage-be kerülnek mentésre
- Olvasási sebesség kiválasztása, olvasó indítása, megállítása. A sebesség és az előrehaladás automatikusan mentésre kerül sütibe.
- Bejelentkezés Google Oauth szolgáltatással és a könyvjelzők létrehozása, adatbázisba mentése. A könyv szövege nem kerül mentésre a szerveren.

## Technológia
PHP, JS, CSS, HTMl. 
Framework nem került felhasználása. Külső szolgáltató könyvtárai: google/apiclient (composer), ill. a fronted-backend kommunikációhoz htmx script. Egyes UI részekhez Bootstra, valamint a betűtipusokhoz (IBM Plex Mono) Google Font szervereit használja.

## Telepítés
Követelmény: Web server legalább PHP 8.2-es támogatással, MYSQL adatbázis.

A telepítéshez le kell klónozni a repot: 
```
git clone https://github.com/kalmanba/spread
cd spread
```

Regisztráljunk be a Google Oauth portálra és szerezzünk egy OAuth Client_ID-t és Secretet.

Hozzunk létre egy .env fájlt. Az alábbiakban egy példa. Használhatunk idézőjeleket, de space-t az egyenlőségjelek előtt/után nem.
```
DB_USER=
DB_PASS=
DB_DB=


GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIR_URL="https://example.com/acc/google-callback.php"
```

Ezután állítsuk be a webszerverünket a mappába ahová a repot leklónoztuk. 

Kész is!
