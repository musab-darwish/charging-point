/**
 * Shared form checks for the dialogs. They explain a mistake under its field
 * in formal Arabic (instead of the browser's own bubble), before sending a
 * form the server would refuse anyway. The server still checks everything.
 */

/**
 * A number typed on any keyboard: Arabic digits (٣٫٥) and a comma are fine.
 * NaN when empty or not a number.
 */
export function toNumber(value) {
    const latin = latinDigits(value);

    return /^-?(\d+\.?\d*|\.\d+)$/.test(latin) ? Number(latin) : NaN;
}

export function latinDigits(value) {
    return String(value)
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660))
        .replace(/[٫,،]/g, '.')
        .replace(/\s+/g, '');
}

/**
 * What is wrong with an amount of money, or null when it is fine.
 *
 * @param {string} value
 * @param {{ label: string, empty: string, allowZero?: boolean, max: number }} rules
 */
export function amountProblem(value, { label, empty, allowZero = false, max }) {
    const latin = latinDigits(value);

    if (latin === '') {
        return empty;
    }

    const amount = toNumber(value);

    if (Number.isNaN(amount)) {
        return `اكتب ${label} بالأرقام، مثل 3 أو 2.50.`;
    }

    if (amount < 0) {
        return `لا يمكن أن يكون ${label} بالسالب.`;
    }

    if (amount === 0 && !allowZero) {
        return `${label} يجب أن يكون أكبر من صفر.`;
    }

    if (!/^\d*(\.\d{1,2})?$/.test(latin)) {
        return `يقبل ${label} منزلتين عشريتين على الأكثر.`;
    }

    if (amount > max) {
        return `${label} أكبر من المسموح (${max} ₪).`;
    }

    return null;
}

/**
 * Show a message under a field: the [data-error-for="<field id>"] element
 * (its [data-error-text] part, beside the icon). The field's hint,
 * [data-hint-for], hides while the error shows.
 */
export function showFieldError(form, id, message) {
    const field = form.querySelector(`#${id}`);
    const error = form.querySelector(`[data-error-for="${id}"]`);

    field?.setAttribute('aria-invalid', 'true');
    form.querySelector(`[data-hint-for="${id}"]`)?.setAttribute('hidden', '');

    if (error) {
        (error.querySelector('[data-error-text]') ?? error).textContent = message;
        error.hidden = false;
    }
}

export function clearFieldError(form, id) {
    form.querySelector(`#${id}`)?.removeAttribute('aria-invalid');
    form.querySelector(`[data-error-for="${id}"]`)?.setAttribute('hidden', '');
    form.querySelector(`[data-hint-for="${id}"]`)?.removeAttribute('hidden');
}

/**
 * Check a form before it is sent. `check` returns the first problem as
 * { id, message } (id: the field to mark and focus), or null to send it.
 * Runs in the capture phase, so a refused form never reaches the submit lock.
 */
export function guardForm(form, check) {
    form.noValidate = true;

    form.addEventListener(
        'submit',
        (event) => {
            form.querySelectorAll('[data-error-for]').forEach((error) => clearFieldError(form, error.dataset.errorFor));

            const problem = check(event);

            if (!problem) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            showFieldError(form, problem.id, problem.message);
            form.querySelector(`#${problem.id}`)?.focus();
        },
        { capture: true },
    );

    // Typing again clears that field's message.
    form.addEventListener('input', (event) => {
        if (event.target.id) {
            clearFieldError(form, event.target.id);
        }
    });

    form.addEventListener('change', (event) => {
        const group = event.target.closest('[data-error-group]');

        if (group) {
            clearFieldError(form, group.dataset.errorGroup);
        }
    });
}
