function openLoginModal() {
    const loginModal = document.getElementById('loginModal');
    if (loginModal) {
        loginModal.style.display = 'flex'; // Or use classList if that's how your login modal works
    }
}

function closeLoginModal() {
    const loginModal = document.getElementById('loginModal');
    if (loginModal) {
        loginModal.style.display = 'none';
    }
}