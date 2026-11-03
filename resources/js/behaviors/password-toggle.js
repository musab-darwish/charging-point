/**
 * Show or hide a password field. The toggle button points at its input
 * with data-password-toggle="<input id>".
 */
export function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const showing = input.type === 'text';

            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!showing));
            button.setAttribute('aria-label', showing ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
            input.focus({ preventScroll: true });
        });
    });
}
