<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Models\PriceItem;
use App\Models\Transaction;
use App\Support\Numbers;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Today's work form: a customer (picked, or a new name), the items with
 * quantity and price, and whether they paid on the spot.
 */
class RecordWorkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the new name and accept prices typed with Arabic digits.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(fn ($item) => [
                'price_item_id' => ($item['price_item_id'] ?? null) ?: null,
                'description' => trim((string) ($item['description'] ?? '')),
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => Numbers::normalize($item['unit_price'] ?? null),
            ])
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'customer_id' => $this->input('customer_id') ?: null,
            'new_customer_name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('new_customer_name'))) ?: null,
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
        // A second submit of a form that was already recorded only needs to
        // reach the controller, which then does nothing.
        if ($this->alreadyRecorded()) {
            return [
                'date' => ['required', 'date_format:Y-m-d'],
                'form_token' => ['required', 'uuid'],
            ];
        }

        return [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'customer_id' => ['nullable', 'required_without:new_customer_name', 'integer', 'exists:customers,id'],
            'new_customer_name' => ['nullable', 'string', 'max:100', 'unique:customers,name'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.price_item_id' => ['nullable', 'integer', 'exists:price_items,id'],
            'items.*.description' => ['required', 'string', 'max:150'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0', 'max:9999.99', 'decimal:0,2'],
            'pay_now' => ['boolean'],
            'form_token' => ['required', 'uuid'],
        ];
    }

    /**
     * The walk-in customer only pays on the spot.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $validator->errors()->isEmpty() || $this->alreadyRecorded()) {
                    return;
                }

                if ($this->customer()?->is_walk_in && ! $this->boolean('pay_now')) {
                    $validator->errors()->add('customer_id', 'شغل الزبون العابر يُسجَّل مدفوعاً فوراً. اختر «تسجيل وواصل» أو اكتب اسمه.');
                }
            },
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
            'new_customer_name.unique' => 'يوجد زبون بهذا الاسم. اختره من البحث بدلاً من إضافته من جديد.',
            'items.required' => 'أضف صنفاً واحداً على الأقل.',
            'items.min' => 'أضف صنفاً واحداً على الأقل.',
            'items.*.description.required' => 'اكتب وصف البند.',
            'items.*.unit_price.required' => 'اكتب سعر البند رقم :position.',
            'items.*.unit_price.gt' => 'سعر البند يجب أن يكون أكبر من صفر.',
            'items.*.unit_price.decimal' => 'يقبل السعر منزلتين عشريتين على الأكثر.',
            'date.before_or_equal' => 'لا يمكن تسجيل شغل في يوم لم يأتِ بعد.',
        ];
    }

    /**
     * Whether this exact form was recorded before (a double submit).
     */
    public function alreadyRecorded(): bool
    {
        return Transaction::where('form_token', (string) $this->input('form_token'))->exists();
    }

    /**
     * The customer picked in the form, if any.
     */
    public function customer(): ?Customer
    {
        return $this->input('customer_id') ? Customer::find($this->input('customer_id')) : null;
    }

    /**
     * The day the work is recorded on.
     */
    public function day(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('date'));
    }

    /**
     * The items ready to record, with the category taken from the price list
     * (a free item is "other").
     *
     * @return list<array{price_item_id: int|null, category: string, description: string, quantity: int, unit_price: string}>
     */
    public function items(): array
    {
        $priceItems = PriceItem::whereIn('id', collect($this->validated('items'))->pluck('price_item_id')->filter())->get()->keyBy('id');

        return collect($this->validated('items'))
            ->map(fn (array $item) => [
                'price_item_id' => $item['price_item_id'] ? (int) $item['price_item_id'] : null,
                'category' => $priceItems->get($item['price_item_id'])?->category ?? 'other',
                'description' => $priceItems->get($item['price_item_id'])?->name ?? $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => number_format((float) $item['unit_price'], 2, '.', ''),
            ])
            ->values()
            ->all();
    }
}
