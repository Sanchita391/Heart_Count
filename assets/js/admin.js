const dialog = document.getElementById("delete-dialog");
const deleteId = document.getElementById("delete-user-id");
const deleteText = document.getElementById("delete-text");

document.querySelectorAll("[data-delete-id]").forEach((button) => {
    button.addEventListener("click", () => {
        if (!dialog || !deleteId || !deleteText) {
            return;
        }
        deleteId.value = button.dataset.deleteId || "";
        deleteText.textContent = `Remove ${button.dataset.deleteName || "this player"} and their saved games?`;
        dialog.showModal();
    });
});

document.getElementById("delete-cancel")?.addEventListener("click", () => {
    dialog?.close();
});
