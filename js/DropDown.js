function toggleDropdown(event) {
    // 1. Stop the page from jumping to the top when you click the <a> link
    event.preventDefault();
    event.stopPropagation();  // Prevent the click from bubbling up

    // 2. Find your dropdown menu using its exact ID
    const dropdownMenu = document.getElementById('profile-dropdown-menu');

    // 3. Toggle the visibility
    if (dropdownMenu) {
        dropdownMenu.classList.toggle('show');
    }
}

// 4. Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdownMenu = document.getElementById('profile-dropdown-menu');
    
    // If the menu exists and is showing, and we DID NOT click on the profile button...
    if (dropdownMenu && dropdownMenu.classList.contains('show')) {
        if (!event.target.closest('.dropdown-toggle')) {
            dropdownMenu.classList.remove('show');
        }
    }
});

// 5. Close dropdown when pressing Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdownMenu = document.getElementById('profile-dropdown-menu');
        if (dropdownMenu && dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
        }
    }
});