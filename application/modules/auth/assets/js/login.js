(function () {
    const button = document.querySelector('[data-login-password-toggle]');
    const input = document.getElementById('loginPassword');
    if (!button || !input) return;
    button.addEventListener('click', function () {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        const english = document.documentElement.lang === 'en';
        button.setAttribute('aria-label', visible ? (english ? 'Hide password' : 'Sembunyikan password') : (english ? 'Show password' : 'Tampilkan password'));
        button.querySelector('i').className = visible ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });
})();
