import { createCustomerPicker } from './customer-picker';
import { amountProblem, clearFieldError, guardForm, toNumber } from './forms';

/**
 * The freezer page: "وضع غرض في الفريزر" opens a dialog to pick the customer
 * and describe the item; "استلام" on an item opens the pickup dialog for it,
 * where the price is written. Both explain mistakes under the field, and
 * reopen after the server refuses a submit.
 */
export function initFreezer() {
    initStoreDialog();
    initPickupDialog();
}

function initStoreDialog() {
    const dialog = document.getElementById('freezer-store');

    if (!dialog) {
        return;
    }

    const form = dialog.querySelector('[data-freezer-store-form]');
    const description = form.querySelector('#freezer-description');
    const quantity = form.querySelector('#freezer-quantity');
    const storedOn = form.querySelector('#freezer-stored-on');

    const picker = createCustomerPicker(form, () => {
        if (picker?.chosen()) {
            clearFieldError(form, 'freezer-customer-search');
        }
    });

    const open = () => {
        dialog.showModal();
        (picker.chosen() ? description : form.querySelector('#freezer-customer-search')).focus({ preventScroll: true });
    };

    document.querySelectorAll('[data-open-freezer]').forEach((button) => button.addEventListener('click', open));
    dialog.querySelector('[data-close-freezer]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    guardForm(form, () => {
        if (!picker.chosen()) {
            return { id: 'freezer-customer-search', message: 'اختر الزبون أولاً، أو اكتب اسمه لإضافته.' };
        }

        const text = description.value.trim();

        if (text === '') {
            return { id: 'freezer-description', message: 'اكتب ما الذي وُضع في الفريزر.' };
        }

        if (text.length < 2 || !/\p{L}/u.test(text)) {
            return { id: 'freezer-description', message: 'اكتب وصفاً للغرض بالكلمات، مثل: دجاجة.' };
        }

        if (quantity.value.trim() !== '') {
            const count = toNumber(quantity.value);

            if (!Number.isInteger(count) || count < 1 || count > 99) {
                return { id: 'freezer-quantity', message: 'الكمية عدد صحيح من 1 إلى 99.' };
            }
        }

        if (storedOn.value === '') {
            return { id: 'freezer-stored-on', message: 'اختر تاريخ الوصول.' };
        }

        if (storedOn.value > storedOn.max) {
            return { id: 'freezer-stored-on', message: 'لا يمكن أن يكون تاريخ الوصول في يوم لم يأتِ بعد.' };
        }

        return null;
    });

    const old = JSON.parse(form.dataset.old);

    if (old.customer) {
        picker.choose(old.customer);
    } else if (old.newCustomer) {
        picker.choose({ isNew: true, name: old.newCustomer });
    }

    if (dialog.dataset.openOnLoad === 'true') {
        setTimeout(open, 0);
    }
}

function initPickupDialog() {
    const dialog = document.getElementById('freezer-pickup');

    if (!dialog) {
        return;
    }

    const form = dialog.querySelector('[data-pickup-form]');
    const price = form.querySelector('#pickup-price');
    const buttons = [...document.querySelectorAll('[data-pickup]')];

    const open = (item, { keepPrice = false } = {}) => {
        form.action = item.url;
        form.querySelector('[data-pickup-for]').value = item.id;
        dialog.querySelector('[data-pickup-label]').textContent = item.label;
        dialog.querySelector('[data-pickup-customer]').textContent = item.customer;
        dialog.querySelector('[data-pickup-days]').textContent = item.days;

        if (!keepPrice) {
            price.value = '';
            clearFieldError(form, 'pickup-price');
        }

        dialog.showModal();
        price.focus();
        price.select();
    };

    guardForm(form, () => {
        const problem = amountProblem(price.value, {
            label: 'السعر',
            empty: 'اكتب سعر التجميد، أو 0 إن كان بدون مقابل.',
            allowZero: true,
            max: 9999.99,
        });

        return problem ? { id: 'pickup-price', message: problem } : null;
    });

    buttons.forEach((button) => button.addEventListener('click', () => open(JSON.parse(button.dataset.pickup))));
    dialog.querySelector('[data-close-pickup]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    // The price was refused: open the same item again with what was typed.
    const reopen = dialog.dataset.reopen;
    const item = reopen && buttons.map((button) => JSON.parse(button.dataset.pickup)).find((candidate) => String(candidate.id) === reopen);

    if (item) {
        setTimeout(() => open(item, { keepPrice: true }), 0);
    }
}
