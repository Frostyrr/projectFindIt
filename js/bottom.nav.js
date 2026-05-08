// Highlight the current page in bottom nav
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.mobile-nav-item');
    
    if (navItems.length === 0) return; // Don't run if no bottom nav
    
    navItems.forEach(item => {
        // Remove active class from all items
        item.classList.remove('active');
        
        // Get the href of the nav item
        const href = item.getAttribute('href');
        
        // Check if current page matches
        if (href === currentPath || 
            (currentPath === '/' && (href === 'index.php' || href === '/')) ||
            (currentPath.endsWith('/' + href.replace('.php', ''))) ||
            (href !== '#' && currentPath.includes(href.replace('.php', '')))) {
            item.classList.add('active');
        }
    });
    
    // Special case for home page
    if (currentPath === '/' || currentPath === '/index.php' || currentPath.endsWith('/findit/') || currentPath.endsWith('/findit/index.php')) {
        const homeLink = document.querySelector('.mobile-nav-item[href="index.php"]');
        if (homeLink) homeLink.classList.add('active');
    }
});