import { createCustomerPicker } from './customer-picker';
import { amountProblem, clearFieldError, guardForm } from './forms';

/**
 * Moving the debt notebook over: pick the customer (or type a new name),
 * the amount, Enter. The page comes back with the search ready for the
 * next line of the notebook.
 */
export function initOldDebts() {
    const form = document.querySelector('[data-old-debt-form]');

    if (!form) {
        return;
    }

    const amount = form.querySelector('#debt-amount');

    const picker = createCustomerPicker(form, () => {
        if (picker?.chosen()) {
            clearFieldError(form, 'debt-customer-search');
        }
    });

    guardForm(form, () => {
        if (!picker.chosen()) {
            return { id: 'debt-customer-search', message: 'اختر الزبون أولاً، أو اكتب اسمه لإضافته.' };
        }

        const problem = amountProblem(amount.value, { label: 'المبلغ', empty: 'اكتب المبلغ كما في الدفتر.', max: 99999.99 });

        return problem ? { id: 'debt-amount', message: problem } : null;
    });

    const old = JSON.parse(form.dataset.old);

    if (old.customer) {
        picker.choose(old.customer);
    } else if (old.newCustomer) {
        picker.choose({ isNew: true, name: old.newCustomer });
    }

    // Ready for the next line: the name, or the amount when one was refused.
    (picker.chosen() ? amount : form.querySelector('#debt-customer-search')).focus({ preventScroll: true });
}
