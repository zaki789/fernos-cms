(() => {
    'use strict';

    const root = document.documentElement;
    const body = document.body;
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const collapseButton = document.querySelector('[data-sidebar-collapse]');
    const openButton = document.querySelector('[data-sidebar-open]');
    const themeButton = document.querySelector('[data-theme-toggle]');

    const savedTheme = localStorage.getItem('fernosa-admin-theme');
    if (savedTheme === 'light' || savedTheme === 'dark') {
        root.setAttribute('data-admin-theme', savedTheme);
    }

    const savedSidebar = localStorage.getItem('fernosa-sidebar-collapsed');
    if (savedSidebar === '1' && sidebar && window.innerWidth > 980) {
        sidebar.classList.add('is-collapsed');
        body.classList.add('sidebar-collapsed');
    }

    collapseButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('is-collapsed');
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem(
            'fernosa-sidebar-collapsed',
            sidebar?.classList.contains('is-collapsed') ? '1' : '0'
        );
    });

    const closeMobileSidebar = () => {
        sidebar?.classList.remove('is-mobile-open');
        overlay?.classList.remove('is-visible');
        body.style.overflow = '';
    };

    openButton?.addEventListener('click', () => {
        sidebar?.classList.add('is-mobile-open');
        overlay?.classList.add('is-visible');
        body.style.overflow = 'hidden';
    });

    overlay?.addEventListener('click', closeMobileSidebar);

    document.querySelectorAll('.sidebar-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 980) {
                closeMobileSidebar();
            }
        });
    });

    themeButton?.addEventListener('click', () => {
        const current = root.getAttribute('data-admin-theme') || 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-admin-theme', next);
        localStorage.setItem('fernosa-admin-theme', next);
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.input-shell')?.querySelector('input');
            const icon = button.querySelector('i');

            if (!input) {
                return;
            }

            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            icon?.classList.toggle('fa-eye', visible);
            icon?.classList.toggle('fa-eye-slash', !visible);
        });
    });

    window.setTimeout(() => {
        document.querySelectorAll('.auto-dismiss').forEach((alert) => {
            alert.animate(
                [
                    { opacity: 1, transform: 'translateY(0)' },
                    { opacity: 0, transform: 'translateY(-8px)' }
                ],
                { duration: 260, fill: 'forwards' }
            ).finished.then(() => alert.remove()).catch(() => {});
        });
    }, 4200);

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            closeMobileSidebar();
        }
    });
})();
