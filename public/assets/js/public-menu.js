(function () {
    const header = document.querySelector('.site-header');
    const button = document.querySelector('.menu-toggle');
    const menu = document.getElementById('site-menu-panel');

    if (!header || !button || !menu) {
        return;
    }

    button.addEventListener('click', () => {
        const isOpen = header.classList.toggle('menu-open');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    menu.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            header.classList.remove('menu-open');
            button.setAttribute('aria-expanded', 'false');
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 560) {
            header.classList.remove('menu-open');
            button.setAttribute('aria-expanded', 'false');
        }
    });
})();

(function () {
    const loginToggle = document.querySelector('[data-public-login-toggle]');
    const loginPanel = document.querySelector('[data-public-login-panel]');

    if (!loginToggle || !loginPanel) {
        return;
    }

    const syncLogin = () => {
        loginPanel.hidden = !loginToggle.checked;
        loginPanel.querySelectorAll('input').forEach((input) => {
            input.required = loginToggle.checked;
        });
    };

    loginToggle.addEventListener('change', syncLogin);
    syncLogin();
})();

(function () {
    const minorToggle = document.querySelector('[data-public-minor-toggle]');
    const guardianPanel = document.querySelector('[data-public-guardian-panel]');

    if (!minorToggle || !guardianPanel) {
        return;
    }

    const syncGuardian = () => {
        guardianPanel.hidden = !minorToggle.checked;
        guardianPanel.querySelectorAll('input').forEach((input) => {
            input.required = minorToggle.checked && ['guardian_name', 'guardian_relation', 'guardian_phone'].includes(input.name);
        });
    };

    minorToggle.addEventListener('change', syncGuardian);
    syncGuardian();
})();

(function () {
    const form = document.querySelector('[data-public-event-registration-form]');

    if (!form) {
        return;
    }

    form.querySelectorAll('[data-public-cpf-input]').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = cpfMask(input.value);
            input.setCustomValidity('');
        });

        input.addEventListener('blur', () => {
            const value = input.value.replace(/\D/g, '');
            input.setCustomValidity(value && !isValidCpf(value) ? 'CPF inválido.' : '');
            if (input.validationMessage) {
                input.reportValidity();
            }
        });
    });

    const cepInput = form.querySelector('[data-public-cep-input]');
    const cepSearch = form.querySelector('[data-public-cep-search]');
    const cepStatus = form.querySelector('[data-public-cep-status]');

    if (cepInput && cepSearch) {
        let lastLookup = '';
        let lookupTimer = null;

        cepInput.addEventListener('input', () => {
            cepInput.value = cepMask(cepInput.value);
            cepInput.setCustomValidity('');
            setCepStatus('');

            const cep = cepInput.value.replace(/\D/g, '');
            window.clearTimeout(lookupTimer);
            if (cep.length === 8 && cep !== lastLookup) {
                lookupTimer = window.setTimeout(() => lookupCep(), 450);
            }
        });

        cepSearch.addEventListener('click', () => lookupCep(true));

        async function lookupCep(force) {
            const cep = cepInput.value.replace(/\D/g, '');
            if (cep.length !== 8) {
                cepInput.setCustomValidity('Informe um CEP com 8 dígitos.');
                cepInput.reportValidity();
                return;
            }

            if (!force && cep === lastLookup) {
                return;
            }

            lastLookup = cep;
            cepInput.setCustomValidity('');
            cepSearch.disabled = true;
            setCepStatus('Buscando CEP...');

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                if (!response.ok) {
                    throw new Error('Falha ao consultar CEP.');
                }

                const data = await response.json();
                if (data.erro) {
                    throw new Error('CEP não encontrado.');
                }

                setInputValue('[data-public-address-input]', data.logradouro);
                setInputValue('[data-public-district-input]', data.bairro);
                setInputValue('[data-public-city-input]', data.localidade);
                setInputValue('[data-public-state-input]', data.uf);
                setCepStatus('Endereço preenchido pelo CEP.');
            } catch (error) {
                cepInput.setCustomValidity('CEP não encontrado.');
                cepInput.reportValidity();
                setCepStatus('Não foi possível buscar este CEP.');
            } finally {
                cepSearch.disabled = false;
            }
        }
    }

    function setInputValue(selector, value) {
        const input = form.querySelector(selector);
        if (input && value) {
            input.value = value;
        }
    }

    function setCepStatus(message) {
        if (cepStatus) {
            cepStatus.textContent = message;
        }
    }

    function cepMask(value) {
        return value
            .replace(/\D/g, '')
            .slice(0, 8)
            .replace(/^(\d{5})(\d)/, '$1-$2');
    }

    function cpfMask(value) {
        return value
            .replace(/\D/g, '')
            .slice(0, 11)
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    function isValidCpf(value) {
        const cpf = value.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }

        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += Number(cpf[i]) * (10 - i);
        }

        let digit = 11 - (sum % 11);
        if (digit >= 10) {
            digit = 0;
        }

        if (digit !== Number(cpf[9])) {
            return false;
        }

        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += Number(cpf[i]) * (11 - i);
        }

        digit = 11 - (sum % 11);
        if (digit >= 10) {
            digit = 0;
        }

        return digit === Number(cpf[10]);
    }
})();

(function () {
    document.querySelectorAll('[data-copy-share]').forEach((button) => {
        button.addEventListener('click', async () => {
            const link = button.getAttribute('data-copy-share') || window.location.href;
            try {
                await navigator.clipboard.writeText(link);
                button.textContent = 'Link copiado';
                button.classList.add('is-copied');
                setTimeout(() => {
                    button.textContent = 'Copiar link';
                    button.classList.remove('is-copied');
                }, 1800);
            } catch (error) {
                window.prompt('Copie o link do evento:', link);
            }
        });
    });
})();
