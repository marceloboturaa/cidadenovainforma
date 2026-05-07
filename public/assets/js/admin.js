(function () {
    const body = document.body;
    const toggle = document.querySelector('.admin-menu-toggle');
    const overlay = document.querySelector('[data-admin-menu-close]');
    const sidebarLinks = document.querySelectorAll('.sidebar nav a');
    const focusButtons = document.querySelectorAll('[data-editor-focus]');

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
