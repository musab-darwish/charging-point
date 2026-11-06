<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Support\Numbers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One line moved over from the debt notebook: a customer (picked, or a new
 * name), the amount, and whether they owe it or it is theirs.
 */
class OpeningBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('move-old-debts');
    }

    /**
     * Tidy the new name, and accept an amount typed with Arabic digits.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'new_customer_name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('new_customer_name'))) ?: null,
            'amount' => Numbers::normalize($this->input('amount')),
            'direction' => $this->input('direction') ?: 'owed',
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
            'customer_id' => ['nullable', 'required_without:new_customer_name', 'integer', Rule::exists('customers', 'id')->where('is_walk_in', false)],
            'new_customer_name' => ['nullable', 'string', 'max:100', 'unique:customers,name'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999.99', 'decimal:0,2'],
            'direction' => ['required', Rule::in(['owed', 'credit'])],
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
            'customer_id.required_without' => 'اختر الزبون أولاً، أو اكتب اسمه لإضافته.',
            'customer_id.exists' => 'اختر زبوناً باسمه، فالزبون العابر ليس له رصيد.',
            'new_customer_name.unique' => 'يوجد زبون بهذا الاسم. اختره من البحث بدلاً من إضافته من جديد.',
            'amount.required' => 'اكتب المبلغ كما في الدفتر.',
            'amount.numeric' => 'اكتب المبلغ بالأرقام، مثل 3 أو 2.50.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'amount.max' => 'المبلغ أكبر من المسموح (99999.99 ₪).',
            'amount.decimal' => 'يقبل المبلغ منزلتين عشريتين على الأكثر.',
        ];
    }

    /**
     * The customer picked in the form, if any.
     */
    public function customer(): ?Customer
    {
        return $this->validated('customer_id') ? Customer::find($this->validated('customer_id')) : null;
    }

    public function amount(): string
    {
        return number_format((float) $this->validated('amount'), 2, '.', '');
    }

    /**
     * Whether the customer owes it ("عليه") rather than it being theirs ("له").
     */
    public function owed(): bool
    {
        return $this->validated('direction') === 'owed';
    }
}
