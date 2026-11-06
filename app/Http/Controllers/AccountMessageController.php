<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\AccountMessage;
use Illuminate\Http\JsonResponse;

class AccountMessageController extends Controller
{
    /**
     * A customer's account message, short and in full, with the WhatsApp
     * links. Built only when the dialog opens, so a list of debtors doesn't
     * work out every message up front.
     */
    public function __invoke(Customer $customer): JsonResponse
    {
        abort_if($customer->is_walk_in, 404);

        $message = new AccountMessage($customer);
        $short = $message->short();
        $full = $message->full();

        return response()->json([
            'name' => $customer->name,
            'short' => ['text' => $short, 'whatsapp' => $message->whatsappUrl($short)],
            'full' => ['text' => $full, 'whatsapp' => $message->whatsappUrl($full)],
        ]);
    }
}
