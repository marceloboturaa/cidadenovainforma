(function () {
    document.querySelectorAll('.password-field').forEach((field) => {
        const input = field.querySelector('input[type="password"], input[type="text"]');
        const button = field.querySelector('.password-toggle');

        if (!input || !button) {
            return;
        }

        button.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-label', isHidden ? 'Ocultar senha' : 'Mostrar senha');
            button.setAttribute('title', isHidden ? 'Ocultar senha' : 'Mostrar senha');
            button.classList.toggle('is-visible', isHidden);
        });
    });
})();
