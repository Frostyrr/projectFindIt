// Profile-specific modal functions (renamed to avoid conflicts)
function openProfileModal(id) {
    const modal = document.getElementById(id);
    if (!modal) {
        console.error('Profile modal not found:', id);
        return;
    }
    modal.classList.add('open');
}

function closeProfileModal(id) {
    const modal = document.getElementById(id);
    if (!modal) {
        console.error('Profile modal not found:', id);
        return;
    }
    modal.classList.remove('open');
}

// Close on backdrop click (profile modals only)
document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === el) {
            el.classList.remove('open');
        }
    });
});

// Owner: populate and open item delete modal
function openOwnerItemDelete(id, name) {
    const idField = document.getElementById('ownerDeleteItemId');
    const nameField = document.getElementById('ownerDeleteItemName');
    
    if (!idField) {
        console.error('Owner delete item ID field not found');
        return;
    }
    if (!nameField) {
        console.error('Owner delete item name field not found');
        return;
    }
    
    idField.value = id;
    nameField.textContent = name;
    openProfileModal('ownerDeleteItemModal');
}

// Admin: populate and open item delete modal
function openAdminItemDelete(id, name) {
    const idField = document.getElementById('adminDeleteItemId');
    const nameField = document.getElementById('adminDeleteItemName');
    
    if (!idField) {
        console.error('Admin delete item ID field not found');
        return;
    }
    if (!nameField) {
        console.error('Admin delete item name field not found');
        return;
    }
    
    idField.value = id;
    nameField.textContent = name;
    openProfileModal('adminDeleteItemModal');
}