{{-- Shared fields for adding and editing an account. $account is null when adding. --}}
<div class="space-y-5">
    <x-input name="name" label="اسم البنك أو التطبيق" :value="$account?->name" required autofocus autocomplete="off" list="payment-account-names"
        hint="كما يعرفه الزبون، مثل: بنك فلسطين أو جوال باي." />
    <datalist id="payment-account-names">
        @foreach (['بنك فلسطين', 'جوال باي', 'بال باي', 'البنك الإسلامي الفلسطيني', 'بنك القدس'] as $suggestion)
            <option value="{{ $suggestion }}"></option>
        @endforeach
    </datalist>

    <x-input name="holder_name" label="اسم صاحب الحساب" :value="$account?->holder_name" required autocomplete="off"
        hint="الاسم الذي يراه الزبون عند التحويل، ليتأكد أنه يحوّل إلى الحساب الصحيح." />

    <x-input name="number" label="رقم الحساب أو المحفظة" :value="$account?->number" required autocomplete="off" dir="ltr" inputmode="text"
        class="text-end font-extrabold tracking-wide tabular-nums"
        hint="رقم الجوال للمحفظة، أو رقم الحساب أو الآيبان للبنك." />
</div>
