<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Support\Numbers;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Putting something in the freezer: a named customer (picked, or a new
 * name), what it is, how many, and the day it arrived.
 */
class StoreFreezeItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the text, accept Arabic digits, and default to today.
     */
    protected function prepareForValidation(): void
    {
        $tidy = fn ($value) => preg_replace('/\s+/u', ' ', trim((string) $value)) ?: null;

        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'new_customer_name' => $tidy($this->input('new_customer_name')),
            'description' => $tidy($this->input('description')),
            'quantity' => Numbers::normalize($this->input('quantity')),
            'stored_on' => $this->input('stored_on') ?: now()->format('Y-m-d'),
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
            // The walk-in customer comes back for nothing: an item needs a name.
            'customer_id' => ['nullable', 'required_without:new_customer_name', 'integer', Rule::exists('customers', 'id')->where('is_walk_in', false)],
            'new_customer_name' => ['nullable', 'string', 'max:100', 'unique:customers,name'],
            'description' => ['required', 'string', 'min:2', 'max:150', 'regex:/\p{L}/u'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'stored_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
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
            'customer_id.required_without' => 'اختر زبوناً أو اكتب اسم زبون جديد.',
            'customer_id.exists' => 'اختر زبوناً باسمه، فالغرض يُستلم لاحقاً.',
            'new_customer_name.unique' => 'يوجد زبون بهذا الاسم. اختره من البحث بدلاً من إضافته من جديد.',
            'description.required' => 'اكتب ما الذي وُضع في الفريزر.',
            'description.min' => 'اكتب وصفاً للغرض بالكلمات، مثل: دجاجة.',
            'description.regex' => 'اكتب وصفاً للغرض بالكلمات، مثل: دجاجة.',
            'quantity.integer' => 'الكمية عدد صحيح من 1 إلى 99.',
            'quantity.min' => 'الكمية عدد صحيح من 1 إلى 99.',
            'quantity.max' => 'الكمية عدد صحيح من 1 إلى 99.',
            'stored_on.required' => 'اختر تاريخ الوصول.',
            'stored_on.date_format' => 'اختر تاريخ الوصول.',
            'stored_on.before_or_equal' => 'لا يمكن أن يكون تاريخ الوصول في يوم لم يأتِ بعد.',
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
            'quantity' => 'الكمية',
            'stored_on' => 'تاريخ الوصول',
        ];
    }

    /**
     * The customer picked in the form, if any.
     */
    public function customer(): ?Customer
    {
        return $this->validated('customer_id') ? Customer::find($this->validated('customer_id')) : null;
    }

    /**
     * The day it arrived.
     */
    public function storedOn(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('stored_on'));
    }
}
