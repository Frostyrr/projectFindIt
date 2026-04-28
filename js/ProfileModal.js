function openItemDeleteModal(itemId) {
    document.getElementById('deleteItemModal').classList.add('show-modal');
    document.getElementById('confirmItemDeleteBtn').href = 'delete.php?id=' + itemId;
}

function openAccountDeleteModal() {
    document.getElementById('deleteAccountModal').classList.add('show-modal');
}

function closeModals() {
    document.getElementById('deleteItemModal').classList.remove('show-modal');
    document.getElementById('deleteAccountModal').classList.remove('show-modal');
}

// Listen for clicks on the background overlay to close it
window.addEventListener('click', function(event) {
    const itemModal = document.getElementById('deleteItemModal');
    const accountModal = document.getElementById('deleteAccountModal');
    
    // If the user clicked the dark overlay (not the white card), close it
    if (event.target === itemModal || event.target === accountModal) {
        closeModals();
    }
});