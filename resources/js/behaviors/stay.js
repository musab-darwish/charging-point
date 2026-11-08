// Forms that change something and come back to the page (hide an item,
// move it up, confirm a transfer, record work, a payment...). Sent the usual
// way, each one adds a page to the browser's history, so Back has to be
// pressed once per press to leave. A form[data-stay] is sent in the
// background instead, with an X-Stay header: the server answers with where
// it would have redirected (App\Http\Middleware\StayOnPage), and that page
// opens in place of this one's history entry, where it was scrolled to when
// it is the same page. Messages and errors show as usual (the server keeps
// them for that page).

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
                    headers: { 'X-Stay': '1' },
                    redirect: 'manual',
                    credentials: 'same-origin',
                });
            } catch {
                // No connection: let the browser try the plain way.
                form.submit();
                return;
            }

            // Done, or refused with a message: open the page the server names.
            const location = await stayLocation(response);

            if (location) {
                rememberScroll();
                window.location.replace(location);
                return;
            }

            // A redirect the server didn't turn into an address (one from before
            // this form's own handling), or an address that isn't this site's:
            // reload in place.
            if (response.type === 'opaqueredirect' || isJson(response)) {
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

/**
 * The address in the server's { location } answer, if that is what came back
 * (only ever this site's own pages).
 */
async function stayLocation(response) {
    if (!response.ok || !isJson(response)) {
        return null;
    }

    try {
        const url = new URL((await response.json()).location, window.location.href);

        return url.origin === window.location.origin ? url.href : null;
    } catch {
        return null;
    }
}

function isJson(response) {
    return response.headers.get('content-type')?.includes('application/json') ?? false;
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
