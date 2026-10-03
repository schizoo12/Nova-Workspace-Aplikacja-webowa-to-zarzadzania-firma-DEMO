const toggle = document.querySelector('#togglePassword');
const password = document.querySelector('#password');

toggle.addEventListener('click', () => {
    const visible = password.type === 'password';
    password.type = visible ? 'text' : 'password';
    toggle.textContent = visible ? 'Ukryj' : 'Pokaż';
    toggle.setAttribute('aria-label', visible ? 'Ukryj hasło' : 'Pokaż hasło');
});
