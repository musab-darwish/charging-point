/**
 * The date picker on a day's page jumps straight to the chosen day.
 */
export function initDayNav() {
    document.querySelectorAll('[data-day-picker]').forEach((input) => {
        input.addEventListener('change', () => {
            if (!input.value) {
                return;
            }

            const today = input.max;

            window.location.href = input.value >= today ? input.dataset.todayUrl : input.dataset.url.replace('__DATE__', input.value);
        });
    });
}
