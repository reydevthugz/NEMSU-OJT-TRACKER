'use strict';

(() => {
    const sidebar = document.getElementById('app-sidebar');
    const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
    const closeButton = document.querySelector('[data-sidebar-close]');

    if (!sidebar || toggleButtons.length === 0 || !closeButton) {
        return;
    }

    const mobile = window.matchMedia('(max-width: 991.98px)');
    const setOpen = (isOpen) => {
        document.body.classList.toggle('sidebar-open', isOpen);
        toggleButtons.forEach((button) => {
            button.setAttribute('aria-expanded', String(isOpen));
            button.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
        });
    };

    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setOpen(!document.body.classList.contains('sidebar-open'));
            if (document.body.classList.contains('sidebar-open')) {
                sidebar.querySelector('a')?.focus();
            }
        });
    });

    const close = () => {
        setOpen(false);
        toggleButtons[0].focus();
    };

    closeButton.addEventListener('click', close);
    mobile.addEventListener('change', () => setOpen(false));

    sidebar.addEventListener('click', (event) => {
        if (event.target.closest('a') && mobile.matches) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            close();
        }
    });
})();
