(function () {
    const body = document.body;
    const toggle = document.querySelector('.admin-menu-toggle');
    const overlay = document.querySelector('[data-admin-menu-close]');
    const sidebarLinks = document.querySelectorAll('.sidebar nav a');
    const focusButtons = document.querySelectorAll('[data-editor-focus]');
    const galleryList = document.querySelector('[data-gallery-list]');
    const galleryAdd = document.querySelector('[data-gallery-add]');

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
})();
