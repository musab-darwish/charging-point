<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelling a movement: the reason is required, so a cancellation always
 * says why (the notebook's crossed-out lines never did).
 */
class ReverseTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the reason's spaces.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => preg_replace('/\s+/u', ' ', trim((string) $this->input('reason'))) ?: null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:150', 'regex:/\p{L}/u'],
            'form_token' => ['required', 'uuid'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'اكتب سبب الإلغاء.',
            'reason.min' => 'اكتب سبباً واضحاً للإلغاء، مثل: سُجّلت لزبون آخر.',
            'reason.regex' => 'اكتب سبباً واضحاً للإلغاء، مثل: سُجّلت لزبون آخر.',
            'reason.max' => 'سبب الإلغاء طويل، اختصره إلى 150 حرفاً.',
        ];
    }
}
