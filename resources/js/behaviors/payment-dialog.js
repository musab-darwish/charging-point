import { amountProblem, clearFieldError, guardForm } from './forms';

/**
 * "واصل": a [data-pay] button on a customer's card (or their page) opens the
 * payment dialog with the day's remaining amount filled in, and a one-tap
 * choice to pay the whole balance instead. Cash by default; "تحويل" shows
 * the accounts a transfer can arrive on and its optional number.
 */
export function initPaymentDialog() {
    const dialog = document.getElementById('payment-dialog');

    if (!dialog) {
        return;
    }

    const form = dialog.querySelector('[data-payment-form]');

    const amount = dialog.querySelector('[data-payment-amount]');
    const fills = dialog.querySelectorAll('[data-payment-fill]');
    const methods = dialog.querySelectorAll('[data-payment-method]');
    const transferFields = dialog.querySelector('[data-transfer-fields]');
    const accounts = dialog.querySelectorAll('[data-payment-account]');
    const reference = dialog.querySelector('[data-payment-reference]');
    const notes = dialog.querySelectorAll('[data-payment-note]');
    const submit = dialog.querySelector('[data-payment-submit]');
    let current = null;

    const choose = (which) => {
        amount.value = which === 'balance' ? current.balance : current.remaining;
        fills.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.paymentFill === which)));
    };

    // Show what the chosen method needs. The account is only required for a
    // transfer, so the hidden choices never block a cash payment.
    const showMethod = () => {
        const transfer = dialog.querySelector('[data-payment-method]:checked').value === 'transfer';

        transferFields.hidden = !transfer;
        accounts.forEach((account) => (account.required = transfer));
        notes.forEach((note) => (note.hidden = note.dataset.paymentNote !== (transfer ? 'transfer' : 'cash')));
        submit.textContent = transfer ? 'تسجيل الحوالة' : 'تسجيل الدفعة';
    };

    document.querySelectorAll('[data-pay]').forEach((button) => {
        button.addEventListener('click', () => {
            current = JSON.parse(button.dataset.pay);

            dialog.querySelector('[data-payment-customer]').value = current.id;
            dialog.querySelector('[data-payment-name]').textContent = current.name;
            dialog.querySelector('[data-payment-remaining]').textContent = current.remaining;
            dialog.querySelector('[data-payment-balance]').textContent = current.balance;

            // Every payment starts as cash, with nothing left from the last one.
            methods.forEach((method) => (method.checked = method.value === 'cash'));
            ['payment-amount', 'payment-account', 'payment-reference'].forEach((id) => clearFieldError(form, id));
            if (reference) {
                reference.value = '';
            }
            showMethod();

            // Nothing left from today (an old debt only): suggest the whole balance.
            choose(Number(current.remaining) > 0 ? 'remaining' : 'balance');

            dialog.showModal();
            amount.select();
        });
    });

    methods.forEach((method) => method.addEventListener('change', showMethod));

    guardForm(form, () => {
        const problem = amountProblem(amount.value, { label: 'المبلغ', empty: 'اكتب المبلغ المدفوع.', max: 99999.99 });

        if (problem) {
            return { id: 'payment-amount', message: problem };
        }

        const transfer = form.querySelector('[data-payment-method]:checked').value === 'transfer';

        if (transfer && accounts.length === 0) {
            return { id: 'payment-account', message: 'لا يمكن تسجيل حوالة قبل إضافة حساب تحويل.' };
        }

        if (transfer && !form.querySelector('[data-payment-account]:checked')) {
            return { id: 'payment-account', message: 'اختر الحساب الذي وصلت عليه الحوالة.' };
        }

        return null;
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
