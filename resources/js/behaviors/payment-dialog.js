/**
 * "واصل": a [data-pay] button on a customer's card opens the cash payment
 * dialog with the day's remaining amount filled in, and a one-tap choice
 * to pay the whole balance instead.
 */
export function initPaymentDialog() {
    const dialog = document.getElementById('payment-dialog');

    if (!dialog) {
        return;
    }

    const amount = dialog.querySelector('[data-payment-amount]');
    const fills = dialog.querySelectorAll('[data-payment-fill]');
    let current = null;

    const choose = (which) => {
        amount.value = which === 'balance' ? current.balance : current.remaining;
        fills.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.paymentFill === which)));
    };

    document.querySelectorAll('[data-pay]').forEach((button) => {
        button.addEventListener('click', () => {
            current = JSON.parse(button.dataset.pay);

            dialog.querySelector('[data-payment-customer]').value = current.id;
            dialog.querySelector('[data-payment-name]').textContent = current.name;
            dialog.querySelector('[data-payment-remaining]').textContent = current.remaining;
            dialog.querySelector('[data-payment-balance]').textContent = current.balance;

            // Nothing left from today (an old debt only): suggest the whole balance.
            choose(Number(current.remaining) > 0 ? 'remaining' : 'balance');

            dialog.showModal();
            amount.select();
        });
    });

    fills.forEach((button) => button.addEventListener('click', () => choose(button.dataset.paymentFill)));

    amount.addEventListener('input', () => fills.forEach((button) => button.setAttribute('aria-pressed', 'false')));

    dialog.querySelector('[data-payment-cancel]').addEventListener('click', () => dialog.close());

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
}
