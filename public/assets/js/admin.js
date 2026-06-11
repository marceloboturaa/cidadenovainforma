(function () {
    const body = document.body;
    const toggle = document.querySelector('.admin-menu-toggle');
    const overlay = document.querySelector('[data-admin-menu-close]');
    const sidebarLinks = document.querySelectorAll('.sidebar nav a');
    const sidebarCollapseToggle = document.querySelector('[data-sidebar-collapse-toggle]');
    const focusButtons = document.querySelectorAll('[data-editor-focus]');
    const galleryList = document.querySelector('[data-gallery-list]');
    const galleryAdd = document.querySelector('[data-gallery-add]');
    const personForms = document.querySelectorAll('[data-person-form]');
    const modalOpeners = document.querySelectorAll('[data-modal-open]');
    const educationStudentList = document.querySelector('[data-education-student-list]');
    const educationStudentSearch = document.querySelector('[data-education-student-search]');
    const educationSelectVisible = document.querySelector('[data-education-select-visible]');
    const educationClearVisible = document.querySelector('[data-education-clear-visible]');
    const educationVideoWatch = document.querySelector('[data-education-video-watch]');
    const educationCompleteButtons = document.querySelectorAll('[data-education-complete-button]');
    const usersDirectory = document.querySelector('[data-users-directory]');
    const usersSearch = document.querySelector('[data-users-search]');
    const pendingUsersList = document.querySelector('[data-pending-users-list]');
    const pendingUsersSearch = document.querySelector('[data-pending-users-search]');
    const pendingUsersSelectAll = document.querySelector('[data-pending-users-select-all]');
    const eventsAdminList = document.querySelector('[data-events-admin-list]');
    const eventCepInput = document.querySelector('[data-event-cep-input]');
    const eventCepSearch = document.querySelector('[data-event-cep-search]');
    const registrationDirectory = document.querySelector('[data-registration-directory]');
    const registrationDirectorySearch = document.querySelector('[data-registration-directory-search]');
    const participantSelectAll = document.querySelector('[data-participant-select-all]');
    const linkedParticipants = document.querySelector('[data-linked-participants]');
    const linkedParticipantSearch = document.querySelector('[data-linked-participant-search]');
    const documentList = document.querySelector('[data-document-list]');
    const documentSearch = document.querySelector('[data-document-search]');

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

    groupSidebarLinks();

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

    if (pendingUsersList) {
        bindPendingUsersFilter(pendingUsersList, pendingUsersSearch);
    }

    if (pendingUsersSelectAll) {
        bindCheckboxSelection(pendingUsersSelectAll, '[data-pending-user-select]');
    }

    if (eventsAdminList) {
        bindEventsAdminFilter(eventsAdminList);
    }

    if (eventCepInput && eventCepSearch) {
        bindCepLookup(eventCepInput, eventCepSearch, document, {
            address: '[data-event-address-input]'
        });
    }

    if (registrationDirectory && registrationDirectorySearch) {
        bindRegistrationDirectorySearch(registrationDirectory, registrationDirectorySearch);
    }

    if (participantSelectAll) {
        bindCheckboxSelection(participantSelectAll, '[data-participant-select]');
    }

    if (linkedParticipants && linkedParticipantSearch) {
        bindLinkedParticipantsFilter(linkedParticipants, linkedParticipantSearch);
    }

    bindParticipantEmailForm();

    bindDocumentAccessTools();

    if (documentList && documentSearch) {
        bindDocumentSearch(documentList, documentSearch);
    }

    personForms.forEach((personForm) => {
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

            personForm.querySelectorAll('[data-birth-date-input]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = dateMask(input.value);
                    input.setCustomValidity('');
                });
                input.addEventListener('blur', () => {
                    input.setCustomValidity(input.value && !isValidDate(input.value) ? 'Data inválida. Use dd/mm/aaaa.' : '');
                    if (input.validationMessage) {
                        input.reportValidity();
                    }
                });
            });

            personForm.querySelectorAll('[data-phone-input]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = phoneMask(input.value);
                });
            });

            if (minorToggle && guardianFields) {
                const syncGuardian = () => guardianFields.hidden = !minorToggle.checked;
                minorToggle.addEventListener('change', syncGuardian);
                syncGuardian();
            }

            if (cepInput && cepSearch) {
                bindCepLookup(cepInput, cepSearch, personForm, {
                    address: '[data-address-input]',
                    district: '[data-district-input]',
                    city: '[data-city-input]',
                    state: '[data-state-input]'
                });
            }
        });

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

    function groupSidebarLinks() {
        const nav = document.querySelector('.sidebar nav');
        if (!nav || nav.dataset.grouped === '1') {
            return;
        }

        const links = Array.from(nav.querySelectorAll(':scope > a'));
        if (!links.length) {
            return;
        }

        const groups = [
            { title: 'Geral', items: ['Dashboard', 'Usuários', 'Usuarios'] },
            { title: 'Conteúdo', items: ['Notícias', 'Noticias', 'Categorias', 'Tags'] },
            { title: 'Institucional', items: ['Instituição', 'Instituicao', 'Pessoas', 'Eventos', 'Documentos'] },
            { title: 'Educação', items: ['Ensino', 'Meus certificados', 'Certificados', 'Reconhecimentos', 'Cursos'] },
            { title: 'Comunicação', items: ['Fóruns', 'Foruns'] },
            { title: 'Sistema', items: ['Menu', 'Backups', 'LGPD Cookies'] },
            { title: 'Conta', items: ['Meu cadastro', 'Minha senha'] }
        ];

        nav.textContent = '';

        groups.forEach((group) => {
            const groupItems = group.items.map(normalizeSearch);
            const matched = links.filter((link) => groupItems.includes(normalizeSearch(link.getAttribute('title') || link.textContent || '')));
            if (!matched.length) {
                return;
            }

            const section = document.createElement('div');
            section.className = 'sidebar-group';

            const title = document.createElement('span');
            title.className = 'sidebar-group-title';
            title.textContent = group.title;
            section.appendChild(title);

            matched.forEach((link) => section.appendChild(link));
            nav.appendChild(section);
        });

        links
            .filter((link) => !link.parentElement || !link.closest('.sidebar-group'))
            .forEach((link) => nav.appendChild(link));

        nav.dataset.grouped = '1';
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

    function setValue(scope, selector, value) {
        const input = scope.querySelector(selector);
        if (input && value) {
            input.value = value;
        }
    }

    function bindCepLookup(cepInput, cepSearch, scope, targets) {
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
                setValue(scope, targets.address, data.logradouro);
                if (targets.district) {
                    setValue(scope, targets.district, data.bairro);
                }
                if (targets.city) {
                    setValue(scope, targets.city, data.localidade);
                }
                if (targets.state) {
                    setValue(scope, targets.state, data.uf);
                }
            } catch (error) {
                cepInput.setCustomValidity('CEP não encontrado.');
                cepInput.reportValidity();
            } finally {
                cepSearch.disabled = false;
            }
        });
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

    function bindRegistrationDirectorySearch(directory, searchInput) {
        const cards = Array.from(directory.querySelectorAll('[data-registration-card]'));
        const empty = directory.querySelector('[data-registration-directory-empty]');
        const count = document.querySelector('[data-registration-directory-count]');
        const filterButtons = Array.from(document.querySelectorAll('[data-registration-filter]'));
        const start = document.querySelector('[data-registration-directory-start]');
        let activeFilter = 'all';

        const applyFilter = () => {
            const term = normalizeSearch(searchInput.value);
            let visible = 0;
            const hasQuery = term.length > 0;
            const shouldShowResults = hasQuery || activeFilter !== 'all';

            cards.forEach((card) => {
                const haystack = normalizeSearch(card.dataset.registrationSearch || card.textContent || '');
                const type = card.dataset.registrationType || '';
                const role = card.dataset.registrationRole || '';
                const matchesFilter = activeFilter === 'all' || type === activeFilter || role === activeFilter;
                const matchesSearch = !hasQuery || haystack.includes(term);
                const matches = shouldShowResults && matchesFilter && matchesSearch;
                card.classList.toggle('is-hidden', !matches);
                if (matches) {
                    visible += 1;
                }
            });

            if (count) {
                count.textContent = String(visible);
            }
            if (start) {
                start.hidden = shouldShowResults;
            }
            if (empty) {
                empty.hidden = !shouldShowResults || visible > 0;
            }
        };

        searchInput.addEventListener('input', applyFilter);
        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.registrationFilter || 'available';
                filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                applyFilter();
            });
        });
        applyFilter();
    }

    function bindPendingUsersFilter(list, searchInput) {
        const cards = Array.from(list.querySelectorAll('[data-pending-user-card]'));
        const empty = list.querySelector('[data-pending-users-empty]');
        const countLabel = document.querySelector('[data-pending-users-visible-label]');
        const filterButtons = Array.from(document.querySelectorAll('[data-pending-users-filter]'));
        let activeFilter = 'all';

        const applyFilter = () => {
            const term = normalizeSearch(searchInput ? searchInput.value : '');
            let visible = 0;

            cards.forEach((card) => {
                const haystack = normalizeSearch(card.dataset.pendingUserSearch || card.textContent || '');
                const matchesText = term === '' || haystack.includes(term);
                const matchesRole = activeFilter === 'all' || card.dataset.pendingUserRole === activeFilter;
                const matches = matchesText && matchesRole;
                card.classList.toggle('is-hidden', !matches);
                if (matches) {
                    visible += 1;
                }
            });

            if (countLabel) {
                countLabel.textContent = String(visible);
            }
            if (empty) {
                empty.hidden = visible > 0 || cards.length === 0;
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', applyFilter);
        }
        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.pendingUsersFilter || 'all';
                filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                applyFilter();
            });
        });
        applyFilter();
    }

    function bindCheckboxSelection(toggleButton, inputSelector) {
        const inputs = Array.from(document.querySelectorAll(inputSelector));
        if (!inputs.length) {
            return;
        }

        const syncButton = () => {
            const availableInputs = inputs.filter((input) => !input.disabled);
            const checked = availableInputs.filter((input) => input.checked).length;
            toggleButton.classList.toggle('is-active', availableInputs.length > 0 && checked === availableInputs.length);
            toggleButton.querySelector('i')?.classList.toggle('bi-ui-checks', checked !== availableInputs.length);
            toggleButton.querySelector('i')?.classList.toggle('bi-ui-checks-grid', availableInputs.length > 0 && checked === availableInputs.length);
        };

        toggleButton.addEventListener('click', () => {
            const availableInputs = inputs.filter((input) => !input.disabled);
            const shouldCheck = availableInputs.some((input) => !input.checked);
            availableInputs.forEach((input) => {
                input.checked = shouldCheck;
            });
            syncButton();
        });

        inputs.forEach((input) => input.addEventListener('change', syncButton));
        syncButton();
    }

    function bindLinkedParticipantsFilter(list, searchInput) {
        const cards = Array.from(list.querySelectorAll('[data-linked-participant-card]'));
        const empty = list.querySelector('[data-linked-participant-empty]');
        const count = document.querySelector('[data-linked-participant-count]');
        const filterButtons = Array.from(document.querySelectorAll('[data-linked-participant-filter]'));
        const exportStatus = document.querySelector('[data-participant-export-status]');
        const limitButton = document.querySelector('[data-linked-participant-limit]');
        let activeFilter = 'all';
        let visibleLimit = 10;

        const applyFilter = () => {
            const term = normalizeSearch(searchInput.value);
            let matched = 0;

            cards.forEach((card) => {
                const haystack = normalizeSearch(card.dataset.linkedParticipantSearch || card.textContent || '');
                const matchesText = term === '' || haystack.includes(term);
                const matchesStatus = activeFilter === 'all' || card.dataset.linkedParticipantStatus === activeFilter;
                const matchesFilter = matchesText && matchesStatus;
                const matchesLimit = matched < visibleLimit;
                const matches = matchesFilter && matchesLimit;
                if (matchesFilter) {
                    matched += 1;
                }
                card.querySelectorAll('[data-participant-select]').forEach((input) => {
                    input.disabled = !matches;
                    if (!matches) {
                        input.checked = false;
                    }
                });
                card.classList.toggle('is-hidden', !matches);
            });

            if (count) {
                count.textContent = String(matched);
            }
            if (empty) {
                empty.hidden = matched > 0 || cards.length === 0;
            }

            if (exportStatus) {
                exportStatus.value = activeFilter === 'all' ? '' : activeFilter;
            }
            if (limitButton) {
                limitButton.hidden = visibleLimit >= matched;
                limitButton.setAttribute('aria-pressed', 'false');
                limitButton.innerHTML = '<i class="bi bi-list" aria-hidden="true"></i>Mostrar mais 10';
            }
        };

        searchInput.addEventListener('input', () => {
            visibleLimit = 10;
            applyFilter();
        });
        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.linkedParticipantFilter || 'all';
                visibleLimit = 10;
                filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                applyFilter();
            });
        });
        if (limitButton) {
            limitButton.addEventListener('click', () => {
                visibleLimit += 10;
                applyFilter();
            });
        }
        applyFilter();
    }

    function bindParticipantEmailForm() {
        const form = document.querySelector('[data-participant-email-form]');
        if (!form) {
            return;
        }

        form.addEventListener('submit', (event) => {
            form.querySelectorAll('[data-email-selected-hidden]').forEach((input) => input.remove());
            const mode = form.querySelector('[data-participant-email-mode]')?.value || 'all';
            if (mode !== 'selected') {
                return;
            }

            const selected = Array.from(document.querySelectorAll('[data-participant-select]:checked'));
            if (!selected.length) {
                event.preventDefault();
                window.alert('Marque pelo menos um participante na lista abaixo.');
                return;
            }

            selected.forEach((checkbox) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'person_ids[]';
                hidden.value = checkbox.value;
                hidden.dataset.emailSelectedHidden = '1';
                form.appendChild(hidden);
            });
        });
    }

    function bindDocumentAccessTools() {
        document.querySelectorAll('[data-document-access-tools]').forEach((tools) => {
            const details = tools.closest('.document-access-details, .document-rule-panel') || document;
            const list = details.querySelector('[data-document-access-list]');
            const search = tools.querySelector('[data-document-access-search]');
            const selectButton = tools.querySelector('[data-document-access-select]');
            const clearButton = tools.querySelector('[data-document-access-clear]');

            if (!list) {
                return;
            }

            const visibleInputs = () => Array.from(list.querySelectorAll('[data-document-access-item]:not(.is-hidden) input[type="checkbox"]'));

            if (search) {
                search.addEventListener('input', () => {
                    const term = normalizeSearch(search.value);

                    list.querySelectorAll('[data-document-access-item]').forEach((label) => {
                        const haystack = normalizeSearch(label.dataset.documentAccessItem || label.textContent || '');
                        label.classList.toggle('is-hidden', term !== '' && !haystack.includes(term));
                    });
                });
            }

            if (selectButton) {
                selectButton.addEventListener('click', () => {
                    visibleInputs().forEach((input) => {
                        input.checked = true;
                    });
                });
            }

            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    visibleInputs().forEach((input) => {
                        input.checked = false;
                    });
                });
            }
        });
    }

    function bindDocumentSearch(list, searchInput) {
        const rows = Array.from(list.querySelectorAll('[data-document-row]'));
        const empty = list.querySelector('[data-document-empty]');
        const count = document.querySelector('[data-document-visible-count]');

        const applyFilter = () => {
            const term = normalizeSearch(searchInput.value);
            let visible = 0;

            rows.forEach((row) => {
                const haystack = normalizeSearch(row.dataset.documentSearchText || row.textContent || '');
                const matches = term === '' || haystack.includes(term);
                row.classList.toggle('is-hidden', !matches);
                if (matches) {
                    visible += 1;
                }
            });

            if (count) {
                count.textContent = String(visible);
            }
            if (empty) {
                empty.hidden = visible > 0 || rows.length === 0;
            }
        };

        searchInput.addEventListener('input', applyFilter);
        applyFilter();
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

    function dateMask(value) {
        return value
            .replace(/\D/g, '')
            .slice(0, 8)
            .replace(/(\d{2})(\d)/, '$1/$2')
            .replace(/(\d{2})(\d)/, '$1/$2');
    }

    function phoneMask(value) {
        const digits = value.replace(/\D/g, '').slice(0, 11);
        if (digits.length <= 10) {
            return digits
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        }

        return digits
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d)/, '$1-$2');
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

    function isValidDate(value) {
        const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!match) {
            return false;
        }

        const day = Number(match[1]);
        const month = Number(match[2]);
        const year = Number(match[3]);
        const date = new Date(year, month - 1, day);

        return date.getFullYear() === year
            && date.getMonth() === month - 1
            && date.getDate() === day
            && year >= 1900
            && year <= new Date().getFullYear();
    }
})();
