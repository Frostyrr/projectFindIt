function openfbDeleteModal(id, subject) {
    document.getElementById('deleteFeedbackId').value = id;
    document.getElementById('deleteModalText').textContent =
        'Are you sure you want to delete "' + subject + '"?';
    document.getElementById('deleteModal').classList.add('active'); // ← was 'open'
}

function closefbDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active'); // ← was 'open'
}

document.getElementById('deleteModal').addEventListener('click', function (e) {
    if (e.target === this) closefbDeleteModal();
});