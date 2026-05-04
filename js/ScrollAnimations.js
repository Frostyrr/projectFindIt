/* ============================================================
   FindIt — Subtle Scroll Animations
   ============================================================ */

document.addEventListener('DOMContentLoaded', function() {
    
    // ── Observe elements as they enter viewport ──────────────
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target); // animate once
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -30px 0px'
    });

    // Observe all animated elements
    document.querySelectorAll('.fade-up, .hero-text, .hero-buttons, .hero-scroll-cue, .stagger-cards').forEach(el => {
        observer.observe(el);
    });

    // ── Navbar behavior ──────────────────────────────────────
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;
    let scrollTimeout;

    if (navbar) {
        window.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;

            // Add shadow immediately
            navbar.classList.toggle('scrolled', currentScroll > 10);

            // Hide navbar only after scrolling down significantly (past 300px)
            if (currentScroll > 300 && currentScroll > lastScroll + 20) {
                navbar.classList.add('nav-hidden');
            } else if (currentScroll < lastScroll) {
                navbar.classList.remove('nav-hidden');
            }

            lastScroll = currentScroll;

            // Ensure navbar reappears when near top
            if (currentScroll < 100) {
                navbar.classList.remove('nav-hidden');
            }
        }, { passive: true });
    }

    // ── Smooth scroll for anchor links ───────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

});