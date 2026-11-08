// Buttons that change something and come back to the same page (hide an
// item, move it up, confirm a transfer...). Sent the usual way, each press
// adds the same page to the browser's history again, so Back has to be
// pressed once per press to leave. A form[data-stay] is sent in the
// background instead, and the page reloads in place of its history entry,
// where it was scrolled to. Messages and errors show as usual (the server
// keeps them for the reload).

const SCROLL_KEY = 'stay-scroll';

export function initStayingForms() {
    restoreScroll();

    document.querySelectorAll('form[data-stay]').forEach((form) => {
        // Bubble phase: the confirm dialog (capture) and the submit lock run first.
        form.addEventListener('submit', async (event) => {
            if (event.defaultPrevented) {
                return;
            }

            event.preventDefault();

            let response;

            try {
                response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form, event.submitter),
                    redirect: 'manual',
                    credentials: 'same-origin',
                });
            } catch {
                // No connection: let the browser try the plain way.
                form.submit();
                return;
            }

            // The server sent us back (done, or refused with a message): reload in place.
            if (response.type === 'opaqueredirect') {
                rememberScroll();
                window.location.replace(window.location.href);
                return;
            }

            // Anything else (an error page): show it as the browser would have.
            document.open();
            document.write(await response.text());
            document.close();
        });
    });
}

function rememberScroll() {
    try {
        sessionStorage.setItem(SCROLL_KEY, JSON.stringify({ url: window.location.href, y: window.scrollY }));
    } catch {
        // Storage blocked: the page just opens at the top.
    }
}

function restoreScroll() {
    try {
        const saved = JSON.parse(sessionStorage.getItem(SCROLL_KEY) ?? 'null');
        sessionStorage.removeItem(SCROLL_KEY);

        if (saved?.url === window.location.href) {
            window.scrollTo({ top: saved.y, behavior: 'instant' });
        }
    } catch {
        // Nothing saved, or storage blocked.
    }
}
