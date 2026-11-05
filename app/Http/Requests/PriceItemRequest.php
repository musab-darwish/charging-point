<?php

namespace App\Http\Requests;

use App\Models\PriceItem;
use App\Support\Numbers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adding and editing an item on the price list. An empty price means the
 * amount is entered each time the item is recorded.
 */
class PriceItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-prices');
    }

    /**
     * Tidy the name, and accept a price typed with Arabic digits.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'price' => Numbers::normalize($this->input('price')),
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
            'name' => ['required', 'string', 'max:60', Rule::unique('price_items', 'name')->ignore($this->route('price'))],
            'category' => ['required', Rule::in(array_keys(PriceItem::CATEGORIES))],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
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
            'name.unique' => 'يوجد صنف بهذا الاسم.',
            'price.decimal' => 'يقبل السعر منزلتين عشريتين على الأكثر، مثل 1.50',
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
            'name' => 'اسم الصنف',
            'category' => 'النوع',
        ];
    }
}
