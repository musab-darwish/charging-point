const SEARCH_WAIT_MS = 200;
const MAX_QUANTITY = 99;

/**
 * Today's work form: pick a customer, tap items to build the list, then
 * record it (on account, or paid on the spot). On a phone the form is a
 * full-screen sheet. Validation still happens on the server.
 */
export function initWorkEntry() {
    const panel = document.querySelector('[data-work-entry]');
    const form = panel?.querySelector('[data-work-form]');

    if (!form) {
        return;
    }

    const saveOnAccount = form.querySelector('[data-save-on-account]');
    const savePaid = form.querySelector('[data-save-paid]');
    const walkInNote = form.querySelector('[data-walk-in-note]');

    const update = () => {
        const chosen = picker.chosen();
        const ready = chosen !== null && cart.count() > 0;

        savePaid.disabled = !ready;
        saveOnAccount.disabled = !ready || Boolean(chosen?.walkIn);
        walkInNote.hidden = !chosen?.walkIn;
    };

    const picker = createPicker(form, update);
    const cart = createCart(panel, form, update);
    const sheet = createSheet(panel);

    // An Enter key in a field must never submit the form by surprise.
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target instanceof HTMLInputElement) {
            event.preventDefault();
        }
    });

    form.querySelectorAll('[data-add-item]').forEach((button) => {
        button.addEventListener('click', () => cart.addItem(JSON.parse(button.dataset.addItem)));
    });

    form.querySelector('[data-add-free]')?.addEventListener('click', () => cart.addFree());

    // "+ إضافة" on a customer's card: start a new entry for them.
    document.querySelectorAll('[data-entry-customer]').forEach((button) => {
        button.addEventListener('click', () => {
            picker.choose(JSON.parse(button.dataset.entryCustomer));
            sheet.open();
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            form.querySelector('[data-add-item], [data-add-free]')?.focus({ preventScroll: true });
        });
    });

    restoreOldInput(form, picker, cart);
    update();
}

/**
 * The customer search: recent customers when empty, matches as you type,
 * the walk-in customer, and "add as a new customer" for an unknown name.
 */
function createPicker(form, onChange) {
    const root = form.querySelector('[data-customer-picker]');
    const searchBox = root.querySelector('[data-customer-search]');
    const input = searchBox.querySelector('input');
    const results = root.querySelector('[data-customer-results]');
    const chosenBox = root.querySelector('[data-customer-chosen]');
    const idField = form.querySelector('[data-customer-id]');
    const newField = form.querySelector('[data-new-customer]');
    const walkIn = JSON.parse(root.dataset.walkIn);

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
                ...(term === '' || walkIn.name.includes(term) ? [walkIn] : []),
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

    const choose = (customer) => {
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
            choose(options[active]);
        } else if (event.key === 'Escape') {
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

/**
 * The list of items being recorded, with quantity and price per line.
 */
function createCart(panel, form, onChange) {
    const list = form.querySelector('[data-cart]');
    const empty = form.querySelector('[data-cart-empty]');
    const totalEl = form.querySelector('[data-cart-total]');
    const template = panel.querySelector('[data-line-template]');
    let nextIndex = 0;

    const recalculate = () => {
        let total = 0;

        list.querySelectorAll('[data-line]').forEach((line) => {
            const quantity = toNumber(line.querySelector('[data-field="quantity"]').value) || 0;
            const price = toNumber(line.querySelector('[data-field="unit_price"]').value) || 0;
            const lineTotal = Math.round(quantity * price * 100) / 100;

            line.querySelector('[data-line-total]').textContent = lineTotal.toFixed(2);
            total += lineTotal;
        });

        totalEl.textContent = total.toFixed(2);
        empty.hidden = list.children.length > 0;
        onChange();
    };

    const addLine = ({ id = null, name = '', price = null, description = null, quantity = 1, unitPrice = null }) => {
        const line = template.content.firstElementChild.cloneNode(true);
        const index = nextIndex++;
        const field = (key) => line.querySelector(`[data-field="${key}"]`);

        line.querySelectorAll('[data-field]').forEach((input) => {
            input.name = `items[${index}][${input.dataset.field}]`;
        });

        field('price_item_id').value = id ?? '';
        field('quantity').value = quantity;
        field('unit_price').value = unitPrice ?? price ?? '';

        const nameEl = line.querySelector('[data-line-name]');
        const descriptionInput = field('description');

        if (id) {
            nameEl.textContent = name;
            descriptionInput.type = 'hidden';
            descriptionInput.value = name;
        } else {
            nameEl.remove();
            descriptionInput.value = description ?? '';
        }

        line.dataset.itemId = id ?? '';

        const setQuantity = (value) => {
            field('quantity').value = Math.min(MAX_QUANTITY, Math.max(1, value));
            recalculate();
        };

        line.querySelector('[data-qty-up]').addEventListener('click', () => setQuantity((toNumber(field('quantity').value) || 1) + 1));
        line.querySelector('[data-qty-down]').addEventListener('click', () => setQuantity((toNumber(field('quantity').value) || 1) - 1));
        field('quantity').addEventListener('input', recalculate);
        field('unit_price').addEventListener('input', recalculate);
        line.querySelector('[data-remove-line]').addEventListener('click', () => {
            line.remove();
            recalculate();
        });

        list.append(line);
        recalculate();

        return line;
    };

    const addItem = (item) => {
        // Tapping the same item again raises its quantity.
        const existing = [...list.querySelectorAll('[data-line]')].find(
            (line) => line.dataset.itemId === String(item.id),
        );

        if (existing) {
            existing.querySelector('[data-qty-up]').click();
            existing.classList.remove('animate-rise');
            void existing.offsetWidth;
            existing.classList.add('animate-rise');
            return;
        }

        const line = addLine(item);

        // An item without a fixed price asks for one right away.
        if (item.price === null) {
            line.querySelector('[data-field="unit_price"]').focus();
        }
    };

    const addFree = () => addLine({}).querySelector('[data-field="description"]').focus();

    return { addItem, addFree, addLine, count: () => list.children.length };
}

/**
 * On a phone, the form is a sheet that slides up over the page.
 */
function createSheet(panel) {
    const phone = window.matchMedia('(max-width: 1023px)');

    const setOpen = (open) => {
        panel.dataset.open = String(open);
        document.documentElement.classList.toggle('overflow-hidden', open && phone.matches);
    };

    document.querySelectorAll('[data-open-entry]').forEach((button) => button.addEventListener('click', () => setOpen(true)));
    panel.querySelector('[data-close-entry]')?.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.dataset.open === 'true' && !document.querySelector('dialog[open]')) {
            setOpen(false);
        }
    });

    setOpen(panel.dataset.open === 'true');

    return { open: () => setOpen(true) };
}

/**
 * After a validation error, rebuild the form as it was sent.
 */
function restoreOldInput(form, picker, cart) {
    const old = JSON.parse(form.dataset.old || '{}');

    if (old.customer) {
        picker.choose(old.customer);
    } else if (old.newCustomer) {
        picker.choose({ isNew: true, name: old.newCustomer });
    }

    Object.values(old.items ?? {}).forEach((item) => {
        const button = item.price_item_id ? form.querySelector(`[data-add-item*='"id":${Number(item.price_item_id)},']`) : null;
        const known = button ? JSON.parse(button.dataset.addItem) : null;

        cart.addLine({
            id: item.price_item_id || null,
            name: known?.name ?? item.description,
            price: known?.price ?? null,
            description: item.description,
            quantity: item.quantity || 1,
            unitPrice: item.unit_price,
        });
    });
}

/**
 * Read a number typed on an Arabic keyboard (١٫٥) as well as a normal one.
 */
function toNumber(value) {
    const latin = String(value)
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660))
        .replace(/[٫,،]/g, '.')
        .trim();

    return latin === '' ? NaN : Number(latin);
}
