<!-- components/delete-item-modal.php -->
<div id="deleteItemModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this item? This action cannot be undone.</p>
        
        <!-- The form submits to the root delete_item.php -->
        <form action="../delete_item.php" method="POST">
            <!-- Hidden input to hold the item ID -->
            <input type="hidden" name="item_id" id="modal_item_id" value="">
            
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="confirm-delete-btn">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>