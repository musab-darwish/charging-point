<?php

namespace App\Http\Requests;

use App\Support\Numbers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adding and editing an account customers transfer to.
 */
class PaymentAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-payment-accounts');
    }

    /**
     * Tidy the spaces, and accept a number typed with Arabic digits. The
     * number is kept without spaces, so the same number is always written
     * the same way (and can't be added twice).
     */
    protected function prepareForValidation(): void
    {
        $tidy = fn ($value) => preg_replace('/\s+/u', ' ', trim((string) $value));

        $this->merge([
            'name' => $tidy($this->input('name')),
            'holder_name' => $tidy($this->input('holder_name')),
            'number' => $tidy(Numbers::normalize($this->input('number'))),
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
            'name' => ['required', 'string', 'max:60'],
            'holder_name' => ['required', 'string', 'max:100'],
            // A wallet number, an account number or an IBAN: digits and letters, maybe spaced or dashed.
            'number' => [
                'required', 'string', 'max:50', 'regex:/^[\p{L}\p{N}][\p{L}\p{N} -]*$/u',
                Rule::unique('payment_accounts')->where('name', $this->input('name'))->ignore($this->route('account')),
            ],
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
            'number.unique' => 'هذا الرقم مضاف من قبل على '.$this->input('name').'.',
            'number.regex' => 'يقبل الرقم الأرقام والحروف والمسافات والشرطات فقط.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم البنك أو التطبيق',
            'holder_name' => 'اسم صاحب الحساب',
            'number' => 'رقم الحساب',
        ];
    }
}
