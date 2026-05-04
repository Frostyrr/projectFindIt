<div class="modal-overlay" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="modal">
        <div class="modal-header">
            <h3 id="editModalTitle"><i class="fas fa-pen"></i> Edit Item <span id="edit_id_label"></span></h3>
            <button class="modal-close" onclick="closeModal('editModal')" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="dashboard.php" id="editForm">
            <input type="hidden" name="action"  value="edit">
            <input type="hidden" name="item_id" id="edit_item_id">

            <div class="modal-body">

                <div class="modal-form-row">
                    <div class="modal-form-group">
                        <label for="edit_item_name">Item Name <span class="req">*</span></label>
                        <input type="text" id="edit_item_name" name="item_name"
                               placeholder="e.g. Brown Leather Backpack" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="edit_location">Location <span class="req">*</span></label>
                        <input type="text" id="edit_location" name="location"
                               placeholder="e.g. Gaisano Mall" required>
                    </div>
                </div>

                <div class="modal-form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
                              placeholder="Colour, size, brand, distinguishing features…"></textarea>
                </div>

                <div class="modal-form-row three-col">
                    <div class="modal-form-group">
                        <label for="edit_type">Type</label>
                        <select id="edit_type" name="type">
                            <option value="lost">Lost</option>
                            <option value="found">Found</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="claimed">Claimed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label for="edit_date">Date Lost / Found</label>
                        <input type="date" id="edit_date" name="date_lost_found">
                    </div>
                </div>

            </div><!-- /modal-body -->

            <div class="modal-footer">
                <button type="button" class="modal-btn-cancel" onclick="closeModal('editModal')">
                    Cancel
                </button>
                <button type="submit" class="modal-btn-primary">
                    <i class="fas fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>