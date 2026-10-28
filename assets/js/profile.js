const openButton = document.getElementById("logout-open");
const dialog = document.getElementById("logout-dialog");
const cancelButton = document.getElementById("logout-cancel");

if (openButton && dialog) {
    openButton.addEventListener("click", () => dialog.showModal());
}

if (cancelButton && dialog) {
    cancelButton.addEventListener("click", () => dialog.close());
}
