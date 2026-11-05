const SEARCH_WAIT_MS = 200;
const MAX_QUANTITY = 99;

/**
 * Today's work form, in a dialog: pick a customer, tap items to build the
 * list, then record it (on account, or paid on the spot). Full screen on a
 * phone, a large window on a laptop. Validation still happens on the server.
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

    const errorBox = form.querySelector('[data-entry-error]');
    const pickerRoot = form.querySelector('[data-customer-picker]');

    const showError = (message, focus) => {
        errorBox.querySelector('[data-entry-error-text]').textContent = message;
        errorBox.hidden = false;
        focus?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        focus?.focus({ preventScroll: true });
    };

    const update = () => {
        const chosen = picker.chosen();

        // Only the walk-in customer can't go on account; anything else that is
        // missing is explained when a button is pressed.
        saveOnAccount.disabled = Boolean(chosen?.walkIn);
        walkInNote.hidden = !chosen?.walkIn;

        if (chosen) {
            pickerRoot.dataset.missing = 'false';
        }

        errorBox.hidden = true;
    };

    // Say what is missing instead of submitting (runs before the submit lock).
    form.addEventListener(
        'submit',
        (event) => {
            const problem = findProblem(form, picker);

            if (!problem) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            if (problem.field === 'customer') {
                pickerRoot.dataset.missing = 'true';
            } else {
                problem.input?.setAttribute('aria-invalid', 'true');
            }

            showError(problem.message, problem.input);
        },
        { capture: true },
    );

    form.addEventListener('input', (event) => event.target.removeAttribute?.('aria-invalid'));

    const picker = createPicker(form, update);
    const cart = createCart(panel, form, update);
    const modal = createModal(panel, form, picker);

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

    // Digits 1-9 add the matching item, unless typing in a field.
    document.addEventListener('keydown', (event) => {
        const typing = event.target.closest?.('input:not([type="hidden"]), textarea, select');

        if (typing || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        // "+" opens the recording window from the day page.
        if (event.key === '+' && !document.querySelector('dialog[open]')) {
            event.preventDefault();
            modal.open();
            return;
        }

        const tile = /^[1-9]$/.test(event.key) && form.querySelector(`[data-shortcut="${event.key}"]`);

        if (tile && panel.open && document.querySelectorAll('dialog[open]').length === 1) {
            event.preventDefault();
            tile.click();
            tile.focus({ preventScroll: true });
        }
    });

    // "+ إضافة" on a customer's card: start a new entry for them.
    document.querySelectorAll('[data-entry-customer]').forEach((button) => {
        button.addEventListener('click', () => {
            picker.choose(JSON.parse(button.dataset.entryCustomer));
            modal.open();
        });
    });

    restoreOldInput(form, picker, cart);
    update();
}

/**
 * The first thing stopping the form from being recorded, if any.
 */
function findProblem(form, picker) {
    if (!picker.chosen()) {
        return { field: 'customer', message: 'اختر الزبون أولاً.', input: form.querySelector('#entry-customer-search') };
    }

    const lines = [...form.querySelectorAll('[data-line]')];

    if (lines.length === 0) {
        return { field: 'items', message: 'أضف صنفاً واحداً على الأقل.', input: form.querySelector('[data-add-item], [data-add-free]') };
    }

    for (const line of lines) {
        const description = line.querySelector('[data-field="description"]');

        if (description.type !== 'hidden' && !/\p{L}/u.test(description.value)) {
            return { field: 'line', message: 'اكتب وصفاً للبند بالكلمات، مثل: طحن سكر.', input: description };
        }

        const price = line.querySelector('[data-field="unit_price"]');

        if (!(toNumber(price.value) > 0)) {
            return { field: 'line', message: 'اكتب سعر كل بند، أكبر من صفر.', input: price };
        }
    }

    return null;
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
            form.querySelector('[data-add-item], [data-add-free]')?.focus({ preventScroll: true });
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
 * The recording dialog: opened by "تسجيل شغل", the "+" key or a card's
 * "إضافة". Closing keeps what was entered, so reopening carries on.
 */
function createModal(dialog, form, picker) {
    const open = () => {
        if (!dialog.open) {
            dialog.showModal();
        }

        document.documentElement.classList.add('overflow-hidden');

        // Start where the work starts: the customer, or the items once one is chosen.
        const start = picker.chosen()
            ? form.querySelector('[data-add-item], [data-add-free]')
            : form.querySelector('#entry-customer-search');
        start?.focus({ preventScroll: true });
    };

    dialog.addEventListener('close', () => document.documentElement.classList.remove('overflow-hidden'));

    // A click on the dimmed backdrop (laptop) closes it.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    document.querySelectorAll('[data-open-entry]').forEach((button) => button.addEventListener('click', open));
    dialog.querySelector('[data-close-entry]')?.addEventListener('click', () => dialog.close());

    // After a validation error, show the form again with what was sent
    // (once the old input has been put back).
    if (dialog.dataset.openOnLoad === 'true') {
        setTimeout(open, 0);
    }

    return { open };
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
