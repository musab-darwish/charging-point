import { matchesSearch } from './search-text';

/**
 * Filter a page's lists as you type, without asking the server (everything
 * is already on the page). The search box is input[data-list-filter]; each
 * row is [data-filter-item] with what to search in data-search. A list in
 * [data-filter-group] disappears while none of its rows match, and
 * [data-filter-none] shows when nothing matches at all. "/" jumps to the box.
 */
export function initListFilters() {
    document.querySelectorAll('input[data-list-filter]').forEach((input) => {
        const groups = [...document.querySelectorAll('[data-filter-group]')];
        const items = [...document.querySelectorAll('[data-filter-item]')];
        const none = document.querySelector('[data-filter-none]');
        const typed = document.querySelector('[data-filter-typed]');

        const apply = () => {
            let shown = 0;

            items.forEach((item) => {
                const matches = matchesSearch(item.dataset.search, input.value);
                item.hidden = !matches;
                shown += matches ? 1 : 0;
            });

            groups.forEach((group) => {
                group.hidden = ![...group.querySelectorAll('[data-filter-item]')].some((item) => !item.hidden);
            });

            if (none) {
                none.hidden = shown > 0;
            }

            if (typed) {
                typed.textContent = input.value.trim();
            }
        };

        input.addEventListener('input', apply);

        // Coming back with Back can restore what was typed; filter by it again.
        window.addEventListener('pageshow', apply);

        document.addEventListener('keydown', (event) => {
            const busy = event.target.closest?.('input, textarea, select');

            if (event.key === '/' && !busy && !document.querySelector('dialog[open]')) {
                event.preventDefault();
                input.focus();
                input.select();
            }
        });
    });
}
