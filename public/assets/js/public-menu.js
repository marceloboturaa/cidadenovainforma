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
