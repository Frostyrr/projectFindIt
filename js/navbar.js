/* ============================================================
   FindIt — Navbar JS  |  navbar.js
   Drop this before </body> on every page that includes navbar.php
   ============================================================ */

(function () {
    'use strict';

    // ── Element refs ─────────────────────────────────────────
    const navbar      = document.querySelector('.navbar');
    const hamburger   = document.querySelector('.nav-hamburger');
    const mobileMenu  = document.querySelector('.nav-mobile-menu');
    const overlay     = document.querySelector('.nav-mobile-overlay');

    // ── Scroll → add .scrolled class ─────────────────────────
    if (navbar) {
        const onScroll = () => {
            navbar.classList.toggle('scrolled', window.scrollY > 8);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll(); // run once on load
    }

    // ── Mobile drawer helpers ─────────────────────────────────
    function openMenu() {
        if (!hamburger || !mobileMenu || !overlay) return;
        hamburger.classList.add('open');
        hamburger.setAttribute('aria-expanded', 'true');
        mobileMenu.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // lock scroll
    }

    function closeMenu() {
        if (!hamburger || !mobileMenu || !overlay) return;
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        mobileMenu.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function toggleMenu() {
        mobileMenu && mobileMenu.classList.contains('open') ? closeMenu() : openMenu();
    }

    // ── Hamburger click ───────────────────────────────────────
    if (hamburger) {
        hamburger.addEventListener('click', toggleMenu);
    }

    // ── Overlay click → close ─────────────────────────────────
    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    // ── Escape key → close ────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });

    // ── Close drawer when a mobile link is tapped ────────────
    if (mobileMenu) {
        mobileMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                // Small delay so the page doesn't jump before the drawer closes
                setTimeout(closeMenu, 120);
            });
        });
    }

    // ── Profile dropdown (desktop) ────────────────────────────
    function toggleDropdown(e) {
        e.preventDefault();
        e.stopPropagation(); // keep this click from bubbling to the document listener
        const menu = document.getElementById('profileDropdown');
        if (!menu) return;

        const isOpen = menu.classList.toggle('open');

        if (isOpen) {
            // One-shot listener: first click anywhere outside closes the menu
            const outside = (ev) => {
                if (!menu.contains(ev.target)) {
                    menu.classList.remove('open');
                    document.removeEventListener('click', outside);
                }
            };
            document.addEventListener('click', outside);
        }
    }

    // Expose globally for the inline onclick in navbar.php
    window.toggleDropdown = toggleDropdown;

    // ── Highlight active nav link ─────────────────────────────
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.nav-links a, .nav-mobile-links a').forEach((link) => {
        const href = (link.getAttribute('href') || '').split('/').pop();
        if (href && href === currentPath) {
            link.classList.add('active');
        }
    });
})();