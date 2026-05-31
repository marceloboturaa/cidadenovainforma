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
