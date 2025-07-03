const filename = document.getElementById('filename');


filename.addEventListener('mouseenter', () => {
    originalText = filename.textContent;
    filename.textContent = '❌ Törlés';
    filename.style.cssText = 'justify-self: center; text-align: center; width: 100%;'
});

filename.addEventListener('mouseleave', () => {
    filename.textContent = originalText;
    filename.style.cssText = 'justify-self: end; text-align: end;'
});

filename.addEventListener("click", function () {
    fileLabel.style.display = 'block';
    const submit = document.getElementById('submitBtn');
    submit.remove();
    originalText = "";
    filename.textContent = "";


    const file = document.querySelector('.fileInput');
    file.value = '';
});