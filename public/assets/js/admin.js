(function () {
    const body = document.body;
    const toggle = document.querySelector('.admin-menu-toggle');
    const overlay = document.querySelector('[data-admin-menu-close]');
    const sidebarLinks = document.querySelectorAll('.sidebar nav a');
    const focusButtons = document.querySelectorAll('[data-editor-focus]');
    const galleryList = document.querySelector('[data-gallery-list]');
    const galleryAdd = document.querySelector('[data-gallery-add]');
    const personForm = document.querySelector('[data-person-form]');

    if (toggle) {
        toggle.addEventListener('click', () => {
            const isOpen = body.classList.toggle('admin-menu-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
            closeEditorFocus();
        }
    });

    focusButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const isFocused = document.body.classList.toggle('editor-focus-mode');
            setFocusButtonText(isFocused);
        });
    });

    if (galleryList && galleryAdd) {
        galleryAdd.addEventListener('click', () => {
            const firstCard = galleryList.querySelector('[data-gallery-card]');
            if (!firstCard) {
                return;
            }

            const clone = firstCard.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });
            galleryList.appendChild(clone);
        });

        galleryList.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-gallery-remove]');
            if (!removeButton) {
                return;
            }

            const cards = galleryList.querySelectorAll('[data-gallery-card]');
            const card = removeButton.closest('[data-gallery-card]');

            if (cards.length === 1) {
                card.querySelectorAll('input').forEach((input) => {
                    input.value = '';
                });
                return;
            }

            card.remove();
        });
    }

    if (personForm) {
        const minorToggle = personForm.querySelector('[data-minor-toggle]');
        const guardianFields = personForm.querySelector('[data-guardian-fields]');
        const cepInput = personForm.querySelector('[data-cep-input]');
        const cepSearch = personForm.querySelector('[data-cep-search]');

        personForm.querySelectorAll('[data-cpf-input]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = cpfMask(input.value);
                input.setCustomValidity('');
            });
            input.addEventListener('blur', () => {
                const value = input.value.replace(/\D/g, '');
                input.setCustomValidity(value && !isValidCpf(value) ? 'CPF inválido.' : '');
            });
        });

        if (minorToggle && guardianFields) {
            const syncGuardian = () => guardianFields.hidden = !minorToggle.checked;
            minorToggle.addEventListener('change', syncGuardian);
            syncGuardian();
        }

        if (cepInput && cepSearch) {
            cepInput.addEventListener('input', () => {
                cepInput.value = cepInput.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2').slice(0, 9);
            });
            cepSearch.addEventListener('click', async () => {
                const cep = cepInput.value.replace(/\D/g, '');
                if (cep.length !== 8) {
                    cepInput.setCustomValidity('Informe um CEP com 8 dígitos.');
                    cepInput.reportValidity();
                    return;
                }

                cepInput.setCustomValidity('');
                cepSearch.disabled = true;
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();
                    if (data.erro) {
                        throw new Error('CEP não encontrado.');
                    }
                    setValue('[data-address-input]', data.logradouro);
                    setValue('[data-district-input]', data.bairro);
                    setValue('[data-city-input]', data.localidade);
                    setValue('[data-state-input]', data.uf);
                } catch (error) {
                    cepInput.setCustomValidity('CEP não encontrado.');
                    cepInput.reportValidity();
                } finally {
                    cepSearch.disabled = false;
                }
            });
        }
    }

    function closeMenu() {
        body.classList.remove('admin-menu-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function closeEditorFocus() {
        document.body.classList.remove('editor-focus-mode');
        setFocusButtonText(false);
    }

    function setFocusButtonText(isFocused) {
        focusButtons.forEach((button) => {
            button.textContent = isFocused ? 'Sair do foco' : 'Foco';
        });
    }

    function setValue(selector, value) {
        const input = personForm.querySelector(selector);
        if (input && value) {
            input.value = value;
        }
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
