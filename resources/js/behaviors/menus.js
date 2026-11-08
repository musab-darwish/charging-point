// Drop-down menus built on <details data-menu> (the account menu on the phone).
// A plain <details> only closes from its own button; a menu should also close on
// a tap anywhere else, and on Escape.
//
// While open, the menu shows a see-through backdrop ([data-menu-backdrop]) over
// the page, so that outside tap only closes the menu instead of also pressing
// whatever sits under it.

export function initMenus() {
    const menus = [...document.querySelectorAll('details[data-menu]')];
    if (menus.length === 0) return;

    const close = (menu) => {
        menu.open = false;
    };

    document.addEventListener('click', (event) => {
        for (const menu of menus) {
            if (!menu.open) continue;

            const outside = !menu.contains(event.target) || event.target.closest('[data-menu-backdrop]');
            if (outside) close(menu);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        for (const menu of menus.filter((menu) => menu.open)) {
            close(menu);
            menu.querySelector('summary')?.focus();
        }
    });

    // Coming back with the browser's Back button can restore the page with the menu still open.
    window.addEventListener('pageshow', () => menus.forEach(close));
}
