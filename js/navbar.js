/* ============================================================
   FindIt — Mobile Navbar Toggle
   ============================================================ */

document.addEventListener('DOMContentLoaded', function() {
    
    const hamburger = document.querySelector('.nav-hamburger');
    const mobileMenu = document.querySelector('.nav-mobile-menu');
    const mobileOverlay = document.querySelector('.nav-mobile-overlay');
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;

    // ── Toggle menu ──────────────────────────────────────────
    if (hamburger && mobileMenu && mobileOverlay) {
        hamburger.addEventListener('click', toggleMenu);
        mobileOverlay.addEventListener('click', closeMenu);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && hamburger.classList.contains('active')) {
                closeMenu();
            }
        });
    }

    function toggleMenu() {
        if (hamburger.classList.contains('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    function openMenu() {
        hamburger.classList.add('active');
        mobileMenu.classList.add('active');
        mobileOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        hamburger.classList.remove('active');
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // ── Navbar shadow on scroll ──────────────────────────────
    if (navbar) {
        window.addEventListener('scroll', function() {
            navbar.classList.toggle('scrolled', window.scrollY > 10);

            // Close mobile menu on scroll
            if (hamburger && hamburger.classList.contains('active')) {
                closeMenu();
            }
        }, { passive: true });
    }

});