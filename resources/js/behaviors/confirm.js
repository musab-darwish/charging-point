/**
 * Ask before submitting a form marked with data-confirm="<message>", using
 * the styled dialog in the layout instead of the browser's plain confirm().
 * Optional: data-confirm-title, data-confirm-action (the button's label).
 */
export function initConfirmDialogs() {
    const dialog = document.getElementById('confirm-dialog');

    if (!dialog) {
        return;
    }

    const title = dialog.querySelector('[data-confirm-title]');
    const message = dialog.querySelector('[data-confirm-message]');
    const accept = dialog.querySelector('[data-confirm-accept]');
    let pendingForm = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        // Capture phase, so this runs before the submit lock.
        form.addEventListener(
            'submit',
            (event) => {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                pendingForm = form;
                title.textContent = form.dataset.confirmTitle ?? 'تأكيد';
                message.textContent = form.dataset.confirm;
                accept.textContent = form.dataset.confirmAction ?? 'تأكيد';
                dialog.showModal();
            },
            { capture: true },
        );
    });

    accept.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = 'true';
        dialog.close();
        form.requestSubmit();
    });

    dialog.querySelectorAll('[data-confirm-cancel]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    // A click on the dimmed backdrop cancels.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    dialog.addEventListener('close', () => {
        pendingForm = null;
    });
}
