// Open the delete confirmation modal
function openDeleteModal(id, subject) {
    document.getElementById('deleteFeedbackId').value = id;
    document.getElementById('deleteModalText').textContent =
        'Are you sure you want to delete "' + subject + '"?';
    document.getElementById('deleteModal').classList.add('open');
}

// Close the delete confirmation modal
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}

// Close modal when clicking the dark overlay behind it
document.getElementById('deleteModal').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteModal();
});