const WAIT_MS = 250;

/**
 * Search as you type: a form[data-live-search] fetches its own URL with
 * the query and swaps [data-live-search-results] with the server's list.
 * Without JS the form still works as a plain search.
 */
export function initLiveSearch() {
    document.querySelectorAll('form[data-live-search]').forEach((form) => {
        const input = form.querySelector('input[type="search"]');
        const results = document.querySelector('[data-live-search-results]');
        const spinner = form.querySelector('[data-live-search-spinner]');

        if (!input || !results) {
            return;
        }

        let timer;
        let controller;

        const search = async () => {
            const url = new URL(form.action);

            if (input.value.trim() !== '') {
                url.searchParams.set('q', input.value.trim());
            }

            // A newer search cancels the one still on its way.
            controller?.abort();
            controller = new AbortController();
            spinner?.classList.replace('hidden', 'flex');

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });

                results.innerHTML = await response.text();
                history.replaceState(null, '', url);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    form.submit();
                }
            } finally {
                spinner?.classList.replace('flex', 'hidden');
            }
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(search, WAIT_MS);
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(timer);
            search();
        });
    });
}
