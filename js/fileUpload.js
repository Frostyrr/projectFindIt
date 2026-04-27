const input = document.getElementById("item_image");
const fileName = document.getElementById("file-name");

input.addEventListener("change", function () {
    fileName.textContent = this.files[0]
        ? this.files[0].name
        : "No file chosen";
});