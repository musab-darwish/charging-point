/**
 * The account message dialog: [data-account-message="<url>"] opens it and
 * fetches the customer's message, short and in full. "مختصرة" / "كشف كامل"
 * switches between them; "نسخ" copies the one shown, and WhatsApp opens the
 * customer's number with it (when they have one).
 */
export function initAccountMessage() {
    const dialog = document.getElementById('account-message');

    if (!dialog) {
        return;
    }

    const text = dialog.querySelector('[data-message-text]');
    const loading = dialog.querySelector('[data-message-loading]');
    const failed = dialog.querySelector('[data-message-failed]');
    const copy = dialog.querySelector('[data-copy-message]');
    const copyLabel = dialog.querySelector('[data-copy-label]');
    const whatsapp = dialog.querySelector('[data-whatsapp]');
    const noPhone = dialog.querySelector('[data-no-phone]');
    const kinds = dialog.querySelectorAll('[data-message-kind]');
    const kindNote = dialog.querySelector('[data-message-kind-note]');
    const notes = {
        short: 'آخر دفعة وما بعدها والمتبقي. أرسل الكشف الكامل إن طلب الزبون التفاصيل.',
        full: 'كل يوم بعد آخر دفعة بتاريخه وما سُجّل فيه.',
    };

    let message = null;
    let kind = 'short';

    const show = () => {
        kinds.forEach((tab) => tab.setAttribute('aria-selected', String(tab.dataset.messageKind === kind)));
        kindNote.textContent = notes[kind];

        if (!message) {
            return;
        }

        text.textContent = message[kind].text;
        copy.disabled = false;
        copyLabel.textContent = 'نسخ';

        const link = message[kind].whatsapp;
        whatsapp.toggleAttribute('href', Boolean(link));
        if (link) {
            whatsapp.href = link;
        }
        whatsapp.setAttribute('aria-disabled', String(!link));
        noPhone.hidden = Boolean(link);
    };

    const open = async (url) => {
        message = null;
        kind = 'short';
        text.hidden = true;
        failed.hidden = true;
        loading.hidden = false;
        copy.disabled = true;
        whatsapp.removeAttribute('href');
        whatsapp.setAttribute('aria-disabled', 'true');
        noPhone.hidden = true;
        dialog.querySelector('[data-message-name]').textContent = '';
        show();
        dialog.showModal();

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            message = await response.json();
            dialog.querySelector('[data-message-name]').textContent = message.name;
            loading.hidden = true;
            text.hidden = false;
            show();
        } catch {
            loading.hidden = true;
            failed.hidden = false;
        }
    };

    document.querySelectorAll('[data-account-message]').forEach((button) => {
        button.addEventListener('click', () => open(button.dataset.accountMessage));
    });

    kinds.forEach((tab) => tab.addEventListener('click', () => {
        kind = tab.dataset.messageKind;
        show();
    }));

    copy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(text.textContent);
        } catch {
            // Older browsers: select the text and copy it the old way.
            const range = document.createRange();
            range.selectNodeContents(text);
            getSelection().removeAllRanges();
            getSelection().addRange(range);
            document.execCommand('copy');
            getSelection().removeAllRanges();
        }

        copyLabel.textContent = 'نُسخت';
        setTimeout(() => (copyLabel.textContent = 'نسخ'), 2000);
    });

    dialog.querySelector('[data-close-message]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
}
