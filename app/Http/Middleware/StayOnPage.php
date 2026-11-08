<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For forms sent in the background by resources/js/behaviors/stay.js (they
 * carry an X-Stay header): instead of a redirect the browser would follow
 * on its own, answer with where it leads, so the page can open it in place
 * of its own history entry. Messages and errors stay in the session for
 * that page, as with a redirect. Every other request is left as it is.
 */
class StayOnPage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->hasHeader('X-Stay') || ! $response instanceof RedirectResponse) {
            return $response;
        }

        return new JsonResponse(['location' => $response->getTargetUrl()]);
    }
}
