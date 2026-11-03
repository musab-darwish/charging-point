const INTERACTIVE = 'a[href], button:not(:disabled), summary, label, select, [role="button"], input[type="checkbox"], input[type="radio"]';
const TEXT_FIELD = 'input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]), textarea';
const POSITION_KEY = 'cursor-position';

// How quickly the ring catches up with the dot (0..1, per frame).
const FOLLOW = 0.22;

/**
 * A custom cursor: a red dot (the logo's dot) with a ring that trails it,
 * grows over clickable things and swells on click. Mouse and trackpad only;
 * touch screens keep the normal behaviour. Text fields keep the text cursor.
 *
 * The native cursor is already hidden by a small script in <head>, so there
 * is no flash of the normal cursor while this file loads.
 */
export function initCursor() {
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

    if (!finePointer.matches) {
        document.documentElement.classList.remove('has-custom-cursor');
        return;
    }

    window.__customCursorReady = true;
    document.documentElement.classList.add('has-custom-cursor');

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const dot = document.createElement('div');
    const ring = document.createElement('div');
    dot.className = 'cursor-dot';
    ring.className = 'cursor-ring';
    dot.setAttribute('aria-hidden', 'true');
    ring.setAttribute('aria-hidden', 'true');
    document.body.append(ring, dot);

    // Start where the mouse was on the previous page, so the cursor is there at once.
    const saved = readPosition();
    let mouseX = saved?.x ?? -100;
    let mouseY = saved?.y ?? -100;
    let ringX = mouseX;
    let ringY = mouseY;

    const place = () => {
        dot.style.transform = `translate3d(${mouseX}px, ${mouseY}px, 0)`;
    };

    const show = (visible) => {
        dot.dataset.visible = visible;
        ring.dataset.visible = visible;
    };

    place();
    show(Boolean(saved));

    document.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') {
            return;
        }

        mouseX = event.clientX;
        mouseY = event.clientY;
        place();

        const target = event.target instanceof Element ? event.target : null;
        const overText = target?.closest(TEXT_FIELD);

        show(!overText);
        ring.dataset.hover = Boolean(!overText && target?.closest(INTERACTIVE));
    });

    document.addEventListener('pointerdown', () => {
        ring.dataset.pressed = 'true';
        dot.dataset.pressed = 'true';
    });

    document.addEventListener('pointerup', () => {
        delete ring.dataset.pressed;
        delete dot.dataset.pressed;
    });

    document.documentElement.addEventListener('pointerleave', () => show(false));

    window.addEventListener('pagehide', () => savePosition(mouseX, mouseY));

    keepAboveModals(ring, dot);

    const follow = () => {
        ringX += (mouseX - ringX) * (reduceMotion ? 1 : FOLLOW);
        ringY += (mouseY - ringY) * (reduceMotion ? 1 : FOLLOW);
        ring.style.transform = `translate3d(${ringX}px, ${ringY}px, 0)`;

        requestAnimationFrame(follow);
    };

    requestAnimationFrame(follow);
}

/**
 * A modal <dialog> lives in the browser's top layer, above any z-index.
 * While one is open, the cursor moves inside it so it stays visible.
 */
function keepAboveModals(...elements) {
    const observer = new MutationObserver(() => {
        const openModal = document.querySelector('dialog[open]');
        const host = openModal ?? document.body;

        if (elements[0].parentElement !== host) {
            host.append(...elements);
        }
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        observer.observe(dialog, { attributes: true, attributeFilter: ['open'] });
    });
}

function readPosition() {
    try {
        const value = JSON.parse(sessionStorage.getItem(POSITION_KEY));

        return Number.isFinite(value?.x) && Number.isFinite(value?.y) ? value : null;
    } catch {
        return null;
    }
}

function savePosition(x, y) {
    try {
        sessionStorage.setItem(POSITION_KEY, JSON.stringify({ x, y }));
    } catch {
        // Storage may be blocked; the cursor just starts hidden next time.
    }
}
