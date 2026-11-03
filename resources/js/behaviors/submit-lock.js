/**
 * Lock a form's submit button after the first submit, so a slow network
 * can't send the same form twice. The button shows its busy state through
 * aria-busy, which the button component styles.
 */
export function initSubmitLocks() {
    document.querySelectorAll('form[data-submit-lock]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.setAttribute('aria-busy', 'true');
                button.setAttribute('aria-disabled', 'true');
            });
        });
    });

    // Coming back with the browser's back button restores the page from
    // cache with the lock still on; release it.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) {
            return;
        }

        document.querySelectorAll('form[data-submit-lock]').forEach((form) => {
            delete form.dataset.submitting;

            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.removeAttribute('aria-busy');
                button.removeAttribute('aria-disabled');
            });
        });
    });
}
