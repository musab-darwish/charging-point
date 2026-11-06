<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Support\Numbers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A payment ("واصل"), cash or transfer, from the day page or the customer's page.
 */
class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Cash unless said otherwise, and accept numbers typed with Arabic digits.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'method' => $this->input('method') ?: Transaction::CASH,
            'amount' => Numbers::normalize($this->input('amount')),
            'reference' => Numbers::normalize($this->input('reference')),
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
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_walk_in', false)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999.99', 'decimal:0,2'],
            'method' => ['required', Rule::in([Transaction::CASH, Transaction::TRANSFER])],
            // Only a transfer has an account and a reference; cash drops them.
            'payment_account_id' => ['exclude_unless:method,transfer', 'required', 'integer', Rule::exists('payment_accounts', 'id')->where('is_active', true)],
            'reference' => ['exclude_unless:method,transfer', 'nullable', 'string', 'max:50'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'form_token' => ['required', 'uuid'],
            'back_to_customer' => ['nullable', 'integer', 'exists:customers,id'],
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
            'amount.required' => 'اكتب المبلغ المدفوع.',
            'amount.numeric' => 'اكتب المبلغ بالأرقام، مثل 3 أو 2.50.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'amount.max' => 'المبلغ أكبر من المسموح (99999.99 ₪).',
            'amount.decimal' => 'يقبل المبلغ منزلتين عشريتين على الأكثر.',
            'payment_account_id.required' => 'اختر الحساب الذي وصلت عليه الحوالة.',
            'payment_account_id.exists' => 'هذا الحساب غير متاح للحوالات.',
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
            'reference' => 'رقم الحوالة',
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

    /**
     * The account a transfer arrived on; null for cash.
     */
    public function account(): ?PaymentAccount
    {
        $id = $this->validated('payment_account_id');

        return $id ? PaymentAccount::findOrFail($id) : null;
    }
}
