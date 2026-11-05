<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Support\Numbers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A cash payment ("واصل") from today's work page.
 */
class CashPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept an amount typed with Arabic digits.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['amount' => Numbers::normalize($this->input('amount'))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_walk_in', false)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999.99', 'decimal:0,2'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
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
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'amount.decimal' => 'يقبل المبلغ منزلتين عشريتين على الأكثر.',
        ];
    }

    public function customer(): Customer
    {
        return Customer::findOrFail($this->validated('customer_id'));
    }

    public function amount(): string
    {
        return number_format((float) $this->validated('amount'), 2, '.', '');
    }
}
