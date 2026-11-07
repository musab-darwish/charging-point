import { InvalidDomainError, NotSupportedError, PasskeyExistsError, Passkeys, UserCancelledError } from '@laravel/passkeys';

const OFFER_DISMISSED = 'passkey-offer-dismissed';

/**
 * Fingerprint sign-in (WebAuthn passkeys, through laravel/passkeys):
 * - the login page's "الدخول بالبصمة" button, where the browser supports it;
 * - "تفعيل البصمة" for this device, on the fingerprint page;
 * - on a phone, a one-time offer on the day page until it is set up.
 * The fingerprint itself never leaves the device.
 */
export function initPasskeys() {
    initLogin();
    initRegister();
    initOffer();
}

function initLogin() {
    const box = document.querySelector('[data-passkey-login]');

    if (!box || !Passkeys.isSupported()) {
        return;
    }

    const button = box.querySelector('[data-passkey-verify]');
    const label = box.querySelector('[data-passkey-verify-label]');
    const error = box.querySelector('[data-passkey-error]');
    box.hidden = false;

    button.addEventListener('click', async () => {
        button.disabled = true;
        label.textContent = 'ضع إصبعك على الحساس…';
        error.hidden = true;

        try {
            // Remembered like "تذكّرني", so the phone stays signed in.
            const response = await Passkeys.verify({ remember: true });
            window.location.href = response.redirect ?? '/';
        } catch (problem) {
            button.disabled = false;
            label.textContent = 'الدخول بالبصمة';

            if (!(problem instanceof UserCancelledError)) {
                error.textContent = loginError(problem);
                error.hidden = false;
            }
        }
    });
}

function initRegister() {
    const box = document.querySelector('[data-passkey-register]');

    if (!box) {
        return;
    }

    const button = box.querySelector('[data-passkey-add]');
    const label = box.querySelector('[data-passkey-add-label]');
    const message = box.querySelector('[data-passkey-message]');

    if (!Passkeys.isSupported()) {
        button.disabled = true;
        box.querySelector('[data-passkey-unsupported]').hidden = false;
        return;
    }

    const say = (text, ok) => {
        message.textContent = text;
        message.className = `mt-3 text-sm font-medium ${ok ? 'text-brand' : 'text-debit'}`;
        message.hidden = false;
    };

    button.addEventListener('click', async () => {
        button.disabled = true;
        label.textContent = 'ضع إصبعك على الحساس…';
        message.hidden = true;

        try {
            await Passkeys.register({ name: deviceName() });
            say('فُعّلت البصمة على هذا الجهاز. ادخل بها في المرة القادمة.', true);
            setTimeout(() => window.location.reload(), 1200);
        } catch (problem) {
            button.disabled = false;
            label.textContent = 'تفعيل البصمة';

            if (!(problem instanceof UserCancelledError)) {
                say(registerError(problem), false);
            }
        }
    });
}

function initOffer() {
    const offer = document.querySelector('[data-passkey-offer]');

    // Only on a touch phone that supports it, and only until dismissed here.
    if (!offer || !Passkeys.isSupported() || !matchMedia('(pointer: coarse)').matches || remembered(OFFER_DISMISSED)) {
        return;
    }

    offer.hidden = false;
    offer.querySelector('[data-passkey-offer-dismiss]').addEventListener('click', () => {
        remember(OFFER_DISMISSED);
        offer.hidden = true;
    });
}

function loginError(problem) {
    if (problem instanceof NotSupportedError) {
        return 'هذا المتصفح لا يدعم الدخول بالبصمة.';
    }

    return 'تعذّر الدخول بهذه البصمة. ادخل بكلمة المرور، ثم فعّل البصمة من جديد من «الدخول بالبصمة».';
}

function registerError(problem) {
    if (problem instanceof PasskeyExistsError) {
        return 'البصمة مفعّلة على هذا الجهاز من قبل.';
    }

    if (problem instanceof InvalidDomainError) {
        return 'تعمل البصمة من العنوان الآمن (https) فقط. افتح النظام من عنوانه الآمن وحاول مرة أخرى.';
    }

    return 'تعذّر تفعيل البصمة. حاول مرة أخرى.';
}

/**
 * A readable name for this device in the list: "Android · 6-11-2026".
 */
function deviceName() {
    const agent = navigator.userAgent;
    const kind = [['iPhone', /iPhone/], ['iPad', /iPad/], ['Android', /Android/], ['Windows', /Windows/], ['Mac', /Macintosh/]]
        .find(([, pattern]) => pattern.test(agent))?.[0] ?? 'جهاز';
    const today = new Date();

    return `${kind} · ${today.getDate()}-${today.getMonth() + 1}-${today.getFullYear()}`;
}

function remembered(key) {
    try {
        return localStorage.getItem(key) === '1';
    } catch {
        return false;
    }
}

function remember(key) {
    try {
        localStorage.setItem(key, '1');
    } catch {
        // Private browsing: the offer simply shows again next time.
    }
}
