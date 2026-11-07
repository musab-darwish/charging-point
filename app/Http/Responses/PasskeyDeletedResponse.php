<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;

/**
 * After removing a fingerprint sign-in: back to the page with a message in
 * Arabic (the package's own reply is an English status key).
 */
class PasskeyDeletedResponse implements PasskeyDeletedResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['status' => 'passkey-deleted'], 200);
        }

        return back()->with('status', 'أُزيل الدخول بالبصمة من هذا الجهاز.');
    }
}
