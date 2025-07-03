var wordsArray = [];

document.addEventListener('DOMContentLoaded', function () {
    wordDisplayInstance = new WordDisplay();

});


// Function to save data to a cookie
function saveToCookie(name, value, daysToExpire = 365) {
    const date = new Date();
    date.setTime(date.getTime() + (daysToExpire * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = `${name}=${JSON.stringify(value)}; ${expires}; path=/`;
}

// Function to read data from a cookie
function readFromCookie(name) {
    const cookieName = name + "=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const cookieArray = decodedCookie.split(';');

    for (let i = 0; i < cookieArray.length; i++) {
        let cookie = cookieArray[i];
        while (cookie.charAt(0) === ' ') {
            cookie = cookie.substring(1);
        }
        if (cookie.indexOf(cookieName) === 0) {
            const cookieValue = cookie.substring(cookieName.length, cookie.length);
            try {
                return JSON.parse(cookieValue);
            } catch (e) {
                return cookieValue;
            }
        }
    }
    return null;
}

// Permanent delegated event listener (works for current and future elements)
document.body.addEventListener('succ_ReadBook', function (evt) {
    // Check if this came from an HTMX-processed element if needed
    if (evt.detail.elt) {
        handleSuccess();
    } else {
        handleSuccess();
    }
});

function handleSuccess() {
    var x = document.getElementById("snackbar");
    x.className = "show";
    x.textContent = "Sikeres feltöltés";
    setTimeout(function () { x.className = x.className.replace("show", ""); }, 3000);

    const fileLabel = document.querySelector('[for="fileInput"]');
    if (fileLabel) fileLabel.style.display = 'block';

    const submit = document.getElementById('submitBtn');
    if (submit) submit.remove();

    originalText = "";

    const filename = document.getElementById("filename");
    if (filename) filename.textContent = "";

    const file = document.querySelector('.fileInput');
    if (file) file.value = '';

    function handledata(data) {
        wordsArray = Object.values(data);

        localStorage.setItem("wordsArray", JSON.stringify(wordsArray));

        wordDisplayInstance.stop();
        wordDisplayInstance.reset();


        wordDisplayInstance = new WordDisplay();
        window.wordDisplay = wordDisplayInstance;

    }

    fetch('/backend/file.php?getbook=1') // URL to your PHP endpoint
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json(); // Parse the JSON from the response
        })
        .then(data => {
            handledata(data);
            // You can now use the data (e.g., data.name, data.age)
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });

    fetch('/backend/file.php?getTitleAuthor=1') // URL to your PHP endpoint
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json(); // Parse the JSON from the response
        })
        .then(data => {
            const authorTitleElement = document.getElementById('authorTitle');

            // Save the data to a cookie
            saveToCookie('authorTitleData', data);

            authorTitleElement.textContent = data.author + ': ' + data.title;
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });

}


document.addEventListener('DOMContentLoaded', function () {

    const wordsArrayLocal = JSON.parse(localStorage.getItem("wordsArray"));

    if (wordsArrayLocal) {
        wordsArray = wordsArrayLocal;

        const cachedData = readFromCookie('authorTitleData');
        if (cachedData) {
            const authorTitleElement = document.getElementById('authorTitle');

            authorTitleElement.textContent = cachedData.author + ': ' + cachedData.title;
        }

        wordDisplayInstance = new WordDisplay();
        window.wordDisplay = wordDisplayInstance;

    }

});

function bookmarkFormHandler() {

    wordDisplayInstance.pause();

    wordDisplayState = readFromCookie('wordDisplayState');
    authorTitleData = readFromCookie('authorTitleData')
    document.getElementById('form_wordIndex').value = wordDisplayState.currentIndex;
    document.getElementById('form_speed').value = wordDisplayState.speed;
    document.getElementById('form_title').value = authorTitleData.title;

    htmx.trigger(document.getElementById("bookmarkForm"), "submit");



}