/**
 * Find a customer among a day's cards as you type, or show only one state
 * (لم يُدفع بعد / متبقي / واصل). Everything is already on the page, so it
 * filters instantly without asking the server. "/" jumps to the search box.
 */
export function initDayFilter() {
    const root = document.querySelector('[data-day-filter]');

    if (!root) {
        return;
    }

    const search = root.querySelector('[data-day-filter-search]');
    const statusButtons = [...root.querySelectorAll('[data-day-filter-status]')];
    const cards = [...document.querySelectorAll('[data-day-card]')];
    const noMatch = document.querySelector('[data-day-no-match]');
    let status = 'all';

    const apply = () => {
        const term = normalize(search.value);
        let shown = 0;

        cards.forEach((card) => {
            const matches = (status === 'all' || card.dataset.status === status)
                && (term === '' || normalize(card.dataset.search).includes(term));

            card.hidden = !matches;
            shown += matches ? 1 : 0;
        });

        noMatch.hidden = shown > 0;
    };

    search.addEventListener('input', apply);

    statusButtons.forEach((button) => {
        button.addEventListener('click', () => {
            status = button.dataset.dayFilterStatus;
            statusButtons.forEach((other) => other.setAttribute('aria-pressed', String(other === button)));
            apply();
        });
    });

    document.addEventListener('keydown', (event) => {
        const typing = event.target.closest?.('input, textarea, select');

        if (event.key === '/' && !typing && !document.querySelector('dialog[open]')) {
            event.preventDefault();
            search.focus();
            search.select();
        }
    });
}

/**
 * Match Arabic names the way people type them: أحمد = احمد, فاطمة = فاطمه,
 * مصطفى = مصطفي, with diacritics and extra spaces ignored.
 */
function normalize(text) {
    return String(text)
        .toLowerCase()
        .replace(/[ً-ْـ]/g, '')
        .replace(/[أإآ]/g, 'ا')
        .replace(/ة/g, 'ه')
        .replace(/ى/g, 'ي')
        .replace(/\s+/g, ' ')
        .trim();
}
