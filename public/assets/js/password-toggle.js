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

    const temporaryPasswordChars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';

    function randomIndex(max) {
        if (window.crypto && window.crypto.getRandomValues) {
            const values = new Uint32Array(1);
            window.crypto.getRandomValues(values);
            return values[0] % max;
        }

        return Math.floor(Math.random() * max);
    }

    function generateTemporaryPassword(length) {
        let password = '';

        for (let index = 0; index < length; index++) {
            password += temporaryPasswordChars.charAt(randomIndex(temporaryPasswordChars.length));
        }

        return password;
    }

    async function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        document.body.removeChild(area);
    }

    document.querySelectorAll('[data-temporary-password-form]').forEach((form) => {
        const password = form.querySelector('[data-temporary-password]');
        const confirmation = form.querySelector('[data-temporary-password-confirmation]');
        const generateButton = form.querySelector('[data-generate-temporary-password]');
        const copyButton = form.querySelector('[data-copy-temporary-password]');

        if (!password || !confirmation || !generateButton) {
            return;
        }

        generateButton.addEventListener('click', () => {
            const temporaryPassword = generateTemporaryPassword(12);
            password.value = temporaryPassword;
            confirmation.value = temporaryPassword;
            password.type = 'text';
            confirmation.type = 'text';
            form.querySelectorAll('.password-toggle').forEach((button) => {
                button.setAttribute('aria-label', 'Ocultar senha');
                button.setAttribute('title', 'Ocultar senha');
                button.classList.add('is-visible');
            });
            password.focus();
            password.select();
        });

        if (copyButton) {
            copyButton.addEventListener('click', async () => {
                if (!password.value) {
                    generateButton.click();
                }

                try {
                    await copyText(password.value);
                    const originalLabel = copyButton.innerHTML;
                    copyButton.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i> Copiada';
                    window.setTimeout(() => {
                        copyButton.innerHTML = originalLabel;
                    }, 1800);
                } catch (error) {
                    password.focus();
                    password.select();
                }
            });
        }
    });
})();
