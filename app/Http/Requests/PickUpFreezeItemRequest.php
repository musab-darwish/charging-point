<?php

namespace App\Http\Requests;

use App\Support\Numbers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handing a freezer item back: the price written now (zero for free), and
 * whether it was paid on the spot.
 */
class PickUpFreezeItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept a price typed with Arabic digits.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'price' => Numbers::normalize($this->input('price')),
            'pay_now' => $this->boolean('pay_now'),
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
            'price' => ['required', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
            'pay_now' => ['boolean'],
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
            'price.required' => 'اكتب سعر التجميد، أو 0 إن كان بدون مقابل.',
            'price.numeric' => 'اكتب السعر بالأرقام، مثل 3 أو 2.50.',
            'price.min' => 'لا يمكن أن يكون السعر بالسالب.',
            'price.max' => 'السعر أكبر من المسموح (9999.99 ₪).',
            'price.decimal' => 'يقبل السعر منزلتين عشريتين على الأكثر.',
        ];
    }

    public function price(): string
    {
        return number_format((float) $this->validated('price'), 2, '.', '');
    }
}
