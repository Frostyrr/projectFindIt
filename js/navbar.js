document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburgerBtn');
    const drawer = document.getElementById('navDrawer');
    const overlay = document.getElementById('navOverlay');
    const closeBtn = document.getElementById('closeDrawerBtn');
    
    if (!hamburger || !drawer || !overlay) return;

    function openDrawer() {
        hamburger.classList.add('active');
        hamburger.setAttribute('aria-expanded', 'true');
        hamburger.setAttribute('aria-label', 'Close menu');
        drawer.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        hamburger.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-label', 'Open menu');
        drawer.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (drawer.classList.contains('open')) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer();
    });

    window.closeDrawer = closeDrawer;

    // Navbar scroll shadow
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
});