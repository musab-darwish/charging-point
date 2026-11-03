const VISIBLE_FOR_MS = 4500;
const LEAVE_MS = 220;

/**
 * Flash messages slide in (CSS), then leave on their own after a few
 * seconds or when their close button is pressed.
 */
export function initFlashMessages() {
    document.querySelectorAll('[data-flash]').forEach((flash) => {
        const dismiss = () => {
            if (flash.dataset.leaving) {
                return;
            }

            flash.dataset.leaving = 'true';
            flash.style.transition = `opacity ${LEAVE_MS}ms ease-in, transform ${LEAVE_MS}ms ease-in`;
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-8px)';

            setTimeout(() => flash.remove(), LEAVE_MS);
        };

        let timer = setTimeout(dismiss, VISIBLE_FOR_MS);

        // Keep it while the user is reading it.
        flash.addEventListener('pointerenter', () => clearTimeout(timer));
        flash.addEventListener('pointerleave', () => {
            timer = setTimeout(dismiss, VISIBLE_FOR_MS / 2);
        });

        flash.querySelector('[data-flash-close]')?.addEventListener('click', dismiss);
    });
}
