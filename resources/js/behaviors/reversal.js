import { clearFieldError, guardForm } from './forms';

/**
 * Cancelling a movement entered by mistake. "تصحيح خطأ" on a card (or a
 * statement day) shows a "×" beside each movement; the "×" opens the
 * dialog, where the reason is required. It reopens if the server refuses it.
 */
export function initReversal() {
    document.querySelectorAll('[data-toggle-correcting]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const area = toggle.closest('[data-correctable]');
            const on = area.dataset.correcting !== 'true';

            area.dataset.correcting = String(on);
            toggle.setAttribute('aria-pressed', String(on));
            toggle.querySelector('[data-correcting-label]').textContent = on ? 'إنهاء التصحيح' : 'تصحيح خطأ';

            // Older movements folded away might be the wrong ones: show them too.
            if (on) {
                area.querySelectorAll('details').forEach((details) => (details.open = true));
            }
        });
    });

    const dialog = document.getElementById('reverse-dialog');

    if (!dialog) {
        return;
    }

    const form = dialog.querySelector('[data-reverse-form]');
    const reason = form.querySelector('#reverse-reason');
    const transferOnly = form.querySelector('[data-transfer-only]');
    const buttons = [...document.querySelectorAll('[data-reverse]')];

    const open = (movement, { keepReason = false } = {}) => {
        form.action = movement.url;
        form.querySelector('[data-reverse-for]').value = movement.id;
        dialog.querySelector('[data-reverse-label]').textContent = movement.label;
        dialog.querySelector('[data-reverse-amount]').textContent = movement.amount;
        transferOnly.hidden = !movement.transfer;

        if (!keepReason) {
            reason.value = '';
            clearFieldError(form, 'reverse-reason');
        }

        dialog.showModal();
        reason.focus();
    };

    guardForm(form, () => {
        const text = reason.value.trim();

        if (text === '') {
            return { id: 'reverse-reason', message: 'اكتب سبب الإلغاء.' };
        }

        if (text.length < 3 || !/\p{L}/u.test(text)) {
            return { id: 'reverse-reason', message: 'اكتب سبباً واضحاً للإلغاء، مثل: سُجّلت لزبون آخر.' };
        }

        return null;
    });

    form.querySelectorAll('[data-reverse-reason]').forEach((chip) => {
        chip.addEventListener('click', () => {
            reason.value = chip.dataset.reverseReason;
            clearFieldError(form, 'reverse-reason');
            reason.focus();
        });
    });

    buttons.forEach((button) => button.addEventListener('click', () => open(JSON.parse(button.dataset.reverse))));
    dialog.querySelector('[data-close-reverse]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    // The reason was refused: open the same movement again with what was typed.
    const reopen = dialog.dataset.reopen;
    const movement = reopen && buttons.map((button) => JSON.parse(button.dataset.reverse)).find((candidate) => String(candidate.id) === reopen);

    if (movement) {
        setTimeout(() => open(movement, { keepReason: true }), 0);
    }
}
