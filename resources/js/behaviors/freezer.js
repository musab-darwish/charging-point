import { createCustomerPicker } from './customer-picker';

/**
 * The freezer page: "وضع غرض في الفريزر" opens a dialog to pick the customer
 * and describe the item; "استلام" on an item opens the pickup dialog for it,
 * where the price is written. Both reopen after a refused submit.
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
    const pickerRoot = form.querySelector('[data-customer-picker]');
    const missing = form.querySelector('[data-freezer-missing]');
    const description = form.querySelector('[data-freezer-description]');

    const picker = createCustomerPicker(form, () => {
        if (picker?.chosen()) {
            pickerRoot.dataset.missing = 'false';
            missing.hidden = true;
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

    // The price list's freezing items, one tap to fill the description.
    form.querySelectorAll('[data-freezer-suggestion]').forEach((button) => {
        button.addEventListener('click', () => {
            description.value = button.dataset.freezerSuggestion;
            description.focus();
        });
    });

    // Say what is missing instead of sending a form the server will refuse.
    // Capture phase, so this runs before the submit lock.
    form.addEventListener(
        'submit',
        (event) => {
            if (picker.chosen()) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            pickerRoot.dataset.missing = 'true';
            missing.hidden = false;
            form.querySelector('#freezer-customer-search').focus();
        },
        { capture: true },
    );

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
    const price = form.querySelector('[data-pickup-price]');
    const buttons = [...document.querySelectorAll('[data-pickup]')];

    const open = (item, { keepPrice = false } = {}) => {
        form.action = item.url;
        form.querySelector('[data-pickup-for]').value = item.id;
        dialog.querySelector('[data-pickup-label]').textContent = item.label;
        dialog.querySelector('[data-pickup-customer]').textContent = item.customer;
        dialog.querySelector('[data-pickup-days]').textContent = item.days;

        if (!keepPrice) {
            price.value = '';
        }

        dialog.showModal();
        price.focus();
        price.select();
    };

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
