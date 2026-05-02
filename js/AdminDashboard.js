function openEdit(btn) {
    const d = btn.dataset;

    document.getElementById('edit_item_id').value    = d.id;
    document.getElementById('edit_id_label').textContent = '#' + d.id;
    document.getElementById('edit_item_name').value  = d.name;
    document.getElementById('edit_description').value = d.desc;
    document.getElementById('edit_location').value   = d.location;
    document.getElementById('edit_type').value       = d.type;
    document.getElementById('edit_status').value     = d.status;
    document.getElementById('edit_date').value       = d.date || '';

    openModal('editModal');
    // Focus first field after animation
    setTimeout(() => document.getElementById('edit_item_name').focus(), 80);
}

// ── Open delete modal ────────────────────────────────────────
function openDelete(btn) {
    document.getElementById('delete_item_id').value         = btn.dataset.id;
    document.getElementById('delete_item_name').textContent = btn.dataset.name;
    openModal('deleteModal');
}

// ── Modal helpers ────────────────────────────────────────────
function openModal(id) {
    const overlay = document.getElementById(id);
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal(overlay.id);
    });
});

// Close on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active')
            .forEach(m => closeModal(m.id));
    }
});

// ── Edit form client-side validation ────────────────────────
document.getElementById('editForm').addEventListener('submit', function(e) {
    const name = document.getElementById('edit_item_name').value.trim();
    const loc  = document.getElementById('edit_location').value.trim();
    if (!name || !loc) {
        e.preventDefault();
        alert('Item Name and Location are required.');
    }
});

// ── Auto-dismiss flash after 5 s ────────────────────────────
const flash = document.querySelector('.alert');
if (flash) setTimeout(() => flash.remove(), 5000);