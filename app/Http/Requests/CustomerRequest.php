<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adding and editing a customer. The phone is typed the local way
 * (0599123456) with a prefix, and stored as 970599123456.
 */
class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the input before validating: one space between words in the
     * name, and only digits in the phone.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'phone_local' => preg_replace('/\D/', '', (string) $this->input('phone_local')) ?: null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('customers', 'name')->ignore($customer)],
            'phone_local' => ['nullable', 'regex:/^0?5\d{8}$/'],
            'phone_prefix' => ['required_with:phone_local', Rule::in(Customer::PHONE_PREFIXES)],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'name.unique' => 'يوجد زبون بهذا الاسم. أضف ما يميّزه، مثل اللقب أو اسم العائلة.',
            'phone_local.regex' => 'رقم الجوال غير صحيح. اكتبه هكذا: 0599123456',
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
            'phone_local' => 'رقم الجوال',
            'phone_prefix' => 'مقدمة الرقم',
        ];
    }

    /**
     * The customer's fields, with the phone in its stored form.
     *
     * @return array{name: string, phone: string|null, notes: string|null}
     */
    public function customerData(): array
    {
        $local = $this->validated('phone_local');

        return [
            'name' => $this->validated('name'),
            'phone' => $local ? $this->validated('phone_prefix').ltrim($local, '0') : null,
            'notes' => $this->validated('notes'),
        ];
    }
}
