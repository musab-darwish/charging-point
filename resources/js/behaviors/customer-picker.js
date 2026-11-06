const SEARCH_WAIT_MS = 200;

/**
 * The customer search: recent customers when empty, matches as you type,
 * the walk-in customer (only where the picker offers one, data-walk-in),
 * and "add as a new customer" for an unknown name. After a pick, focus
 * moves to the picker's data-next (the work form's items by default).
 */
export function createCustomerPicker(form, onChange) {
    const root = form.querySelector('[data-customer-picker]');
    const searchBox = root.querySelector('[data-customer-search]');
    const input = searchBox.querySelector('input');
    const results = root.querySelector('[data-customer-results]');
    const chosenBox = root.querySelector('[data-customer-chosen]');
    const idField = form.querySelector('[data-customer-id]');
    const newField = form.querySelector('[data-new-customer]');
    const walkIn = root.dataset.walkIn ? JSON.parse(root.dataset.walkIn) : null;

    let chosen = null;
    let options = [];
    let active = -1;
    let timer;
    let controller;

    const close = () => {
        results.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    };

    const render = () => {
        results.replaceChildren(...options.map((option, index) => optionElement(option, index, index === active)));
        results.hidden = options.length === 0;
        input.setAttribute('aria-expanded', String(!results.hidden));
    };

    const search = async () => {
        const term = input.value.trim();
        const url = new URL(root.dataset.searchUrl, window.location.origin);

        if (term) {
            url.searchParams.set('q', term);
        }

        controller?.abort();
        controller = new AbortController();

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            const customers = await response.json();
            const exact = customers.some((customer) => customer.name === term);

            options = [
                ...(walkIn && (term === '' || walkIn.name.includes(term)) ? [walkIn] : []),
                ...customers,
                ...(term && !exact ? [{ isNew: true, name: term.replace(/\s+/g, ' ') }] : []),
            ];
            active = options.length ? 0 : -1;
            render();
        } catch (error) {
            if (error.name !== 'AbortError') {
                close();
            }
        }
    };

    const choose = (customer, { moveOn = false } = {}) => {
        chosen = customer;
        idField.value = customer.isNew ? '' : customer.id;
        newField.value = customer.isNew ? customer.name : '';

        chosenBox.querySelector('[data-chosen-initial]').textContent = customer.isNew ? '+' : customer.name.charAt(0);
        chosenBox.querySelector('[data-chosen-name]').textContent = customer.name;

        const note = chosenBox.querySelector('[data-chosen-note]');
        const [text, tone] = describe(customer);
        note.textContent = text;
        note.className = `text-xs font-medium ${tone}`;

        chosenBox.hidden = false;
        searchBox.hidden = true;
        close();
        onChange();

        // Picked by the user: go straight on to the items.
        if (moveOn) {
            form.querySelector(root.dataset.next ?? '[data-add-item], [data-add-free]')?.focus({ preventScroll: true });
        }
    };

    const clear = () => {
        chosen = null;
        idField.value = '';
        newField.value = '';
        chosenBox.hidden = true;
        searchBox.hidden = false;
        input.value = '';
        input.focus();
        onChange();
    };

    input.addEventListener('focus', search);
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(search, SEARCH_WAIT_MS);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            active = (active + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length;
            render();
        } else if (event.key === 'Enter' && options[active]) {
            event.preventDefault();
            choose(options[active], { moveOn: true });
        } else if (event.key === 'Escape' && !results.hidden) {
            event.preventDefault();
            close();
        }
    });

    results.addEventListener('mousedown', (event) => event.preventDefault());
    results.addEventListener('click', (event) => {
        const item = event.target.closest('[data-option]');

        if (item) {
            choose(options[Number(item.dataset.option)]);
        }
    });

    input.addEventListener('blur', () => setTimeout(close, 100));
    root.querySelector('[data-change-customer]').addEventListener('click', clear);

    return { chosen: () => chosen, choose };
}

function optionElement(option, index, isActive) {
    const item = document.createElement('li');
    item.setAttribute('role', 'option');
    item.setAttribute('aria-selected', String(isActive));
    item.dataset.option = String(index);
    item.className = `flex cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2 ${isActive ? 'bg-brand-soft' : 'hover:bg-paper'}`;

    const badge = document.createElement('span');
    badge.className = `flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold ${option.isNew ? 'border-2 border-dashed border-brand text-brand' : option.walkIn ? 'bg-paper text-ink-muted' : 'bg-brand text-white'}`;
    badge.textContent = option.isNew ? '+' : option.name.charAt(0);

    const text = document.createElement('span');
    text.className = 'min-w-0 flex-1';

    const name = document.createElement('span');
    name.className = 'block truncate font-bold';
    name.textContent = option.isNew ? `إضافة «${option.name}» كزبون جديد` : option.name;
    text.append(name);

    const [note, tone] = describe(option);
    if (note) {
        const detail = document.createElement('span');
        detail.className = `block truncate text-xs ${tone}`;
        detail.textContent = option.phone && !option.isNew ? `${option.phone} · ${note}` : note;
        text.append(detail);
    }

    item.append(badge, text);

    return item;
}

/**
 * A short line about the customer's account, and its colour.
 */
function describe(customer) {
    if (customer.isNew) {
        return ['يُضاف عند التسجيل', 'text-brand'];
    }

    if (customer.walkIn) {
        return ['يدفع فوراً', 'text-ink-muted'];
    }

    const balance = Number(customer.balance);

    if (balance > 0) {
        return [`عليه ${customer.balance} ₪ من قبل`, 'text-debit'];
    }

    if (balance < 0) {
        return [`له ${customer.balance.replace('-', '')} ₪`, 'text-credit'];
    }

    return ['حسابه مسكّر', 'text-ink-muted'];
}
