(function () {
    const body = document.body;
    const toggle = document.querySelector('.admin-menu-toggle');
    const overlay = document.querySelector('[data-admin-menu-close]');
    const sidebarLinks = document.querySelectorAll('.sidebar nav a');
    const sidebarCollapseToggle = document.querySelector('[data-sidebar-collapse-toggle]');
    const focusButtons = document.querySelectorAll('[data-editor-focus]');
    const galleryList = document.querySelector('[data-gallery-list]');
    const galleryAdd = document.querySelector('[data-gallery-add]');
    const personForm = document.querySelector('[data-person-form]');
    const modalOpeners = document.querySelectorAll('[data-modal-open]');
    const educationStudentList = document.querySelector('[data-education-student-list]');
    const educationStudentSearch = document.querySelector('[data-education-student-search]');
    const educationSelectVisible = document.querySelector('[data-education-select-visible]');
    const educationClearVisible = document.querySelector('[data-education-clear-visible]');
    const educationVideoWatch = document.querySelector('[data-education-video-watch]');
    const educationCompleteButtons = document.querySelectorAll('[data-education-complete-button]');
    const usersDirectory = document.querySelector('[data-users-directory]');
    const usersSearch = document.querySelector('[data-users-search]');
    const eventsAdminList = document.querySelector('[data-events-admin-list]');

    if (toggle) {
        toggle.addEventListener('click', () => {
            const isOpen = body.classList.toggle('admin-menu-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    const storedSidebarState = localStorage.getItem('admin-sidebar-collapsed');
    setSidebarCollapsed(storedSidebarState === null ? true : storedSidebarState === '1');

    if (sidebarCollapseToggle) {
        sidebarCollapseToggle.addEventListener('click', () => {
            const collapsed = !body.classList.contains('admin-sidebar-collapsed');
            setSidebarCollapsed(collapsed);
            localStorage.setItem('admin-sidebar-collapsed', collapsed ? '1' : '0');
        });
    }

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
            closeEditorFocus();
            closeModal();
        }
    });

    modalOpeners.forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.modalOpen || '');
            if (!modal) {
                return;
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            const focusTarget = modal.querySelector('[autofocus], textarea, input, select, button');
            if (focusTarget) {
                window.setTimeout(() => focusTarget.focus(), 80);
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal();
            clearEducationEditParams();
        });
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
            clone.querySelectorAll('input, textarea').forEach((input) => {
                input.value = '';
            });
            clone.querySelectorAll('select').forEach((select) => {
                select.selectedIndex = 0;
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
                card.querySelectorAll('input, textarea').forEach((input) => {
                    input.value = '';
                });
                card.querySelectorAll('select').forEach((select) => {
                    select.selectedIndex = 0;
                });
                return;
            }

            card.remove();
        });
    }

    if (educationStudentList) {
        const visibleStudentInputs = () => Array.from(educationStudentList.querySelectorAll('label:not(.is-hidden) input[type="checkbox"]'));

        if (educationStudentSearch) {
            educationStudentSearch.addEventListener('input', () => {
                const term = educationStudentSearch.value.trim().toLowerCase();

                educationStudentList.querySelectorAll('label[data-student-label]').forEach((label) => {
                    const text = label.dataset.studentLabel || '';
                    label.classList.toggle('is-hidden', term !== '' && !text.includes(term));
                });
            });
        }

        if (educationSelectVisible) {
            educationSelectVisible.addEventListener('click', () => {
                visibleStudentInputs().forEach((input) => {
                    input.checked = true;
                });
            });
        }

        if (educationClearVisible) {
            educationClearVisible.addEventListener('click', () => {
                visibleStudentInputs().forEach((input) => {
                    input.checked = false;
                });
            });
        }
    }

    if (educationVideoWatch) {
        bindEducationVideoWatch(educationVideoWatch);
    }

    if (usersDirectory && usersSearch) {
        bindUsersSearch(usersDirectory, usersSearch);
    }

    if (eventsAdminList) {
        bindEventsAdminFilter(eventsAdminList);
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

    function setSidebarCollapsed(collapsed) {
        body.classList.toggle('admin-sidebar-collapsed', collapsed);

        if (!sidebarCollapseToggle) {
            return;
        }

        sidebarCollapseToggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
        sidebarCollapseToggle.setAttribute('aria-label', collapsed ? 'Mostrar menu lateral' : 'Ocultar menu lateral');
        sidebarCollapseToggle.setAttribute('title', collapsed ? 'Mostrar menu' : 'Ocultar menu');
        const icon = sidebarCollapseToggle.querySelector('i');
        if (icon) {
            icon.className = collapsed ? 'bi bi-layout-sidebar-inset-reverse' : 'bi bi-layout-sidebar-inset';
        }
    }

    function closeEditorFocus() {
        document.body.classList.remove('editor-focus-mode');
        setFocusButtonText(false);
    }

    function closeModal() {
        document.querySelectorAll('.forum-modal.is-open').forEach((modal) => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
        document.body.classList.remove('modal-open');
    }

    function clearEducationEditParams() {
        if (!window.history || !window.history.replaceState) {
            return;
        }

        const url = new URL(window.location.href);
        ['edit_course', 'create_module', 'module_id', 'lesson_id'].forEach((name) => url.searchParams.delete(name));
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
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

    function bindUsersSearch(directory, searchInput) {
        const cards = Array.from(directory.querySelectorAll('[data-user-card]'));
        const empty = directory.querySelector('[data-users-empty]');
        const countBadge = document.querySelector('[data-users-visible-count]');
        const countLabel = document.querySelector('[data-users-visible-label]');
        const filterButtons = Array.from(document.querySelectorAll('[data-users-filter]'));
        let activeFilter = 'all';

        const applyFilter = () => {
            const term = normalizeSearch(searchInput.value);
            let visible = 0;

            cards.forEach((card) => {
                const haystack = normalizeSearch(card.dataset.userSearchText || card.textContent || '');
                const matchesText = term === '' || haystack.includes(term);
                const matchesStatus = activeFilter === 'all' || card.dataset.userStatus === activeFilter;
                const matches = matchesText && matchesStatus;
                card.classList.toggle('is-hidden', !matches);
                if (matches) {
                    visible += 1;
                }
            });

            if (countBadge) {
                countBadge.textContent = String(visible);
            }
            if (countLabel) {
                countLabel.textContent = String(visible);
            }
            if (empty) {
                empty.hidden = visible > 0;
            }
        };

        searchInput.addEventListener('input', applyFilter);
        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.usersFilter || 'all';
                filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                applyFilter();
            });
        });
        applyFilter();
    }

    function normalizeSearch(value) {
        return String(value)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function bindEventsAdminFilter(list) {
        const cards = Array.from(list.querySelectorAll('[data-event-card]'));
        const empty = list.querySelector('[data-events-empty]');
        const buttons = Array.from(document.querySelectorAll('[data-event-filter]'));

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.eventFilter || 'all';
                let visible = 0;

                buttons.forEach((item) => item.classList.toggle('is-active', item === button));
                cards.forEach((card) => {
                    const matches = filter === 'all' || card.dataset.eventBucket === filter;
                    card.classList.toggle('is-hidden', !matches);
                    if (matches) {
                        visible += 1;
                    }
                });

                if (empty) {
                    empty.hidden = visible > 0;
                }
            });
        });
    }

    function bindEducationVideoWatch(player) {
        if (player.tagName === 'VIDEO') {
            player.addEventListener('ended', () => markEducationVideoWatched(player));
            return;
        }

        if (player.tagName === 'IFRAME' && /youtube\.com\/embed\//i.test(player.src)) {
            loadYouTubeApi(() => {
                const id = player.id || `education-youtube-${Date.now()}`;
                player.id = id;
                new window.YT.Player(id, {
                    events: {
                        onStateChange(event) {
                            if (event.data === window.YT.PlayerState.ENDED) {
                                markEducationVideoWatched(player);
                            }
                        }
                    }
                });
            });
        }
    }

    function loadYouTubeApi(callback) {
        if (window.YT && window.YT.Player) {
            callback();
            return;
        }

        const previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function () {
            if (typeof previous === 'function') {
                previous();
            }
            callback();
        };

        if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
            const script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(script);
        }
    }

    async function markEducationVideoWatched(player) {
        if (player.dataset.watchSaved === '1') {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const watchUrl = player.dataset.watchUrl || '';
        if (!token || !watchUrl) {
            return;
        }

        const body = new URLSearchParams();
        body.set('_token', token);

        try {
            const response = await fetch(watchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: body.toString()
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                return;
            }

            player.dataset.watchSaved = '1';
            educationCompleteButtons.forEach((button) => {
                button.disabled = false;
            });
            document.querySelectorAll('[data-education-watch-hint]').forEach((hint) => {
                hint.textContent = 'Vídeo concluído. Agora você pode marcar a aula como concluída.';
                hint.classList.add('is-complete');
            });
        } catch (error) {
            return;
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
