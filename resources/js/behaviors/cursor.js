const INTERACTIVE = 'a[href], button:not(:disabled), summary, label, select, [role="button"], input[type="checkbox"], input[type="radio"]';
const TEXT_FIELD = 'input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]), textarea';

// How quickly the ring catches up with the dot (0..1, per frame).
const FOLLOW = 0.22;

/**
 * A custom cursor: a red dot (the logo's dot) with a ring that trails it,
 * grows over clickable things and swells on click. Mouse and trackpad only;
 * touch screens keep the normal behaviour. Text fields keep the text cursor.
 */
export function initCursor() {
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

    if (!finePointer.matches) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const dot = document.createElement('div');
    const ring = document.createElement('div');
    dot.className = 'cursor-dot';
    ring.className = 'cursor-ring';
    dot.setAttribute('aria-hidden', 'true');
    ring.setAttribute('aria-hidden', 'true');
    document.body.append(ring, dot);
    document.documentElement.classList.add('has-custom-cursor');

    let mouseX = -100;
    let mouseY = -100;
    let ringX = mouseX;
    let ringY = mouseY;

    const show = (visible) => {
        dot.dataset.visible = visible;
        ring.dataset.visible = visible;
    };

    document.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') {
            return;
        }

        mouseX = event.clientX;
        mouseY = event.clientY;
        dot.style.transform = `translate3d(${mouseX}px, ${mouseY}px, 0)`;

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

    const follow = () => {
        ringX += (mouseX - ringX) * (reduceMotion ? 1 : FOLLOW);
        ringY += (mouseY - ringY) * (reduceMotion ? 1 : FOLLOW);
        ring.style.transform = `translate3d(${ringX}px, ${ringY}px, 0)`;

        requestAnimationFrame(follow);
    };

    requestAnimationFrame(follow);
}
