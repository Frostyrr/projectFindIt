<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3 id="deleteModalTitle"><i class="fas fa-trash"></i> Confirm Delete</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="dashboard.php" id="deleteForm">
            <input type="hidden" name="action"  value="delete">
            <input type="hidden" name="item_id" id="delete_item_id">

            <div class="modal-body">
                <div class="delete-warning-icon">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <p class="delete-warning">
                    You are about to permanently delete:<br>
                    <strong id="delete_item_name"></strong><br>
                    <span>This will also remove the uploaded image. This cannot be undone.</span>
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-btn-cancel" onclick="closeModal('deleteModal')">
                    Cancel
                </button>
                <button type="submit" class="modal-btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>