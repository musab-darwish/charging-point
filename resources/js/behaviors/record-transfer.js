import { createCustomerPicker } from './customer-picker';
import { amountProblem, clearFieldError, guardForm } from './forms';

/**
 * «تسجيل حوالة» on the transfers page: a [data-open-transfer] button opens
 * the window, where an existing customer is searched by name or phone and
 * the transfer's amount, account, date and number are entered. Checked here
 * first in the same words as the server, which still checks everything.
 */
export function initRecordTransfer() {
    const dialog = document.querySelector('[data-record-transfer]');
    const form = dialog?.querySelector('[data-transfer-form]');

    if (!form) {
        return;
    }

    const search = form.querySelector('#transfer-customer-search');
    const amount = form.querySelector('#transfer-amount');
    const date = form.querySelector('#transfer-date');
    const accounts = [...form.querySelectorAll('[data-transfer-account]')];

    const picker = createCustomerPicker(form, () => clearFieldError(form, 'transfer-customer-search'));

    const open = () => {
        if (!dialog.open) {
            dialog.showModal();
        }

        // Start where the work starts: the customer, or the amount once one is chosen.
        (picker.chosen() ? amount : search).focus({ preventScroll: true });
    };

    document.querySelectorAll('[data-open-transfer]').forEach((button) => button.addEventListener('click', open));
    form.querySelectorAll('[data-transfer-cancel]').forEach((button) => button.addEventListener('click', () => dialog.close()));

    // A click on the dimmed backdrop closes it.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    guardForm(form, () => {
        if (!picker.chosen()) {
            return { id: 'transfer-customer-search', message: 'اختر الزبون الذي أرسل الحوالة.' };
        }

        const problem = amountProblem(amount.value, { label: 'المبلغ', empty: 'اكتب مبلغ الحوالة.', max: 99999.99 });

        if (problem) {
            return { id: 'transfer-amount', message: problem };
        }

        if (accounts.length === 0) {
            return { id: 'transfer-account', message: 'لا يمكن تسجيل حوالة قبل إضافة حساب تحويل.' };
        }

        if (!accounts.some((account) => account.checked)) {
            return { id: 'transfer-account', message: 'اختر الحساب الذي وصلت عليه الحوالة.' };
        }

        if (date.value === '') {
            return { id: 'transfer-date', message: 'اختر تاريخ الدفعة.' };
        }

        if (date.max && date.value > date.max) {
            return { id: 'transfer-date', message: 'لا يمكن تسجيل دفعة بتاريخ لم يأتِ بعد.' };
        }

        return null;
    });

    // After a refused form, reopen it as it was sent.
    const old = JSON.parse(dialog.dataset.oldCustomer || 'null');

    if (old) {
        picker.choose(old);
    }

    if (dialog.dataset.openOnLoad === 'true') {
        setTimeout(open, 0);
    }
}
