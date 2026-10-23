(() => {
    const button = document.querySelector('.tm-menu-toggle');
    const nav = document.getElementById('tm-navigation');
    if (!button || !nav) return;
    const mobile = window.matchMedia('(max-width: 899px)');
    const label = button.querySelector('.screen-reader-text');
    const setOpen = (open) => {
        button.setAttribute('aria-expanded', String(open));
        nav.classList.toggle('is-open', open);
        label.textContent = open ? 'Fermer le menu' : 'Ouvrir le menu';
    };
    button.hidden = false;
    nav.classList.add('is-enhanced');
    button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
    nav.addEventListener('click', (event) => {
        if (mobile.matches && event.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            button.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('#masthead') && mobile.matches) setOpen(false);
    });
    mobile.addEventListener('change', () => setOpen(false));
})();
