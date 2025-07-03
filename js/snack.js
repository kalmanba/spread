document.body.addEventListener("snacktime", function () {
    var x = document.getElementById("snackbar");
    x.className = "show";
    setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
});

document.body.addEventListener("bookmarkResponseHandler", function () {
  var x = document.getElementById("snackbar");
  x.className = "show";
  setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
  getBookmarkData();
});

document.body.addEventListener("logoutSuccess", function () {
  var x = document.getElementById("snackbar");
  x.innerHTML = "Sikeres kijelentkezés."
  x.className = "show";
  setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
});

const params = new URLSearchParams(window.location.search);
if (params.get('login') === '1') {
  var x = document.getElementById("snackbar");
  x.innerHTML = "Sikeres bejelentkezés."
  x.className = "show";
  setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
  window.history.replaceState({}, document.title, window.location.pathname);

  getBookmarkData();
}
