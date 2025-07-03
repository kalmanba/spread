document.addEventListener('DOMContentLoaded', () => {
  getBookmarkData();
});


function getBookmarkData() {
  const container = document.getElementById("bookmarks-container");

  fetch('/backend/file.php?getBookmarks=1')
    .then(response => {
      if (!response.ok) {
        return response.json().then(err => {
          throw new Error(err.error || 'Könyvjelzők betöltése sikertelen');
        });
      }
      return response.json();
    })
    .then(data => {
      if (data.length === 0) {
        container.textContent = "Nem találhatók könyvjelzők.";
        return;
      }

      // Build table
      const table = document.createElement("table");
      table.classList = 'table table-striped table-bordered';

      // Table header
      const thead = document.createElement("thead");
      thead.innerHTML = `
          <tr>
            <th>Cím</th>
            <th>Elolvasva</th>
            <th>Kiválasztás/Törlés</th>
          </tr>
        `;
      table.appendChild(thead);

      // Table body
      const tbody = document.createElement("tbody");
      data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${escapeHtml(row.title)}</td>
            <td>${row.wordIndex} szó</td>
            <td class="actionButtons">
                <input type="hidden" name="form_use_speed" id="form_use_speed" value="${row.speed}">
                <input type="hidden" name="form_use_wordIndex" id="form_use_wordIndex" value="${row.wordIndex}">
                <button onclick="setBookmarkData();" class="btn btn-success actionButton" >
                  <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none"
                      xmlns="http://www.w3.org/2000/svg" color="#000000">
                      <path d="M7 12.5L10 15.5L17 8.5" stroke="#000000" stroke-width="1.5" stroke-linecap="round"
                          stroke-linejoin="round"></path>
                      <path
                          d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                          stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
                </button>
                <form id="bookmarkDeleteForm" style="justify-self: center" hx-post="/backend/file.php?deleteBookmark=1" hx-target="#snackbar" hx-swap="innerHTML">
                  <input type="hidden" id="form_delete_title" name="form_delete_title" value="${escapeHtml(row.title)}">
                  <button class="btn btn-danger actionButton" type="submit">
                    <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg" color="#000000">
                        <path d="M8.99219 13H11.9922H14.9922" stroke="#000000" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"></path>
                        <path
                            d="M3.03919 4.2939C3.01449 4.10866 3.0791 3.92338 3.23133 3.81499C3.9272 3.31953 6.3142 2 12 2C17.6858 2 20.0728 3.31952 20.7687 3.81499C20.9209 3.92338 20.9855 4.10866 20.9608 4.2939L19.2616 17.0378C19.0968 18.2744 18.3644 19.3632 17.2813 19.9821L16.9614 20.1649C13.8871 21.9217 10.1129 21.9217 7.03861 20.1649L6.71873 19.9821C5.6356 19.3632 4.90325 18.2744 4.73838 17.0378L3.03919 4.2939Z"
                            stroke="#000000" stroke-width="1.5"></path>
                        <path d="M3 5C5.57143 7.66666 18.4286 7.66662 21 5" stroke="#000000" stroke-width="1.5"></path>
                    </svg>
                  </button>
              </form>
            </td>
          `;
        tbody.appendChild(tr);
      });
      table.appendChild(tbody);

      container.innerHTML = ''; 
      container.appendChild(table);
      htmx.process(document.getElementById("bookmarkDeleteForm"));


    })
    .catch(error => {
      container.textContent = `${error.message}`;
    });


  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
}

function setBookmarkData(){

  wordDisplayInstance.stop();
  wordDisplayInstance.reset();

  wordIndex = document.getElementById("form_use_wordIndex").value;
  speed = document.getElementById("form_use_speed").value;
  const state = {
    currentIndex: wordIndex,
    speed: speed
  };

  const expires = new Date();
  expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
  document.cookie = `wordDisplayState=${JSON.stringify(state)};expires=${expires.toUTCString()};path=/`;

  wordDisplayInstance = new WordDisplay();
  window.wordDisplay = wordDisplayInstance;
}