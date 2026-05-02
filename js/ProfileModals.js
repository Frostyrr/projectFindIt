document.addEventListener('DOMContentLoaded', () => {
    const deleteButtons = document.querySelectorAll('.open-delete-modal');
    const deleteModal = document.getElementById('deleteItemModal');
    const hiddenIdInput = document.getElementById('modal_item_id');

    // Attach click event to every delete button on the profile items
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Stop any default button behavior
            
            // Grab the ID from data-id="X" on the button
            const itemId = this.getAttribute('data-id');
            
            if (itemId) {
                // Inject the ID into the form
                hiddenIdInput.value = itemId;
                // Show the modal
                deleteModal.style.display = 'block';
            } else {
                console.error("No item ID found on the delete button.");
            }
        });
    });
});

// Helper to close the modal
function closeDeleteModal() {
    document.getElementById('deleteItemModal').style.display = 'none';
    document.getElementById('modal_item_id').value = ''; // clear it out for safety
}