@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <div class="stagger">
        <a href="{{ route('customers.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            الزبائن
        </a>

        <header>
            <h1 class="text-3xl font-extrabold">تعديل بيانات {{ $customer->name }}</h1>
            <p class="mt-2 text-ink-muted">أُضيف في {{ $customer->created_at->translatedFormat('j F Y') }}.</p>
        </header>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <form method="POST" action="{{ route('customers.update', $customer) }}" data-submit-lock class="rounded-2xl border border-rule bg-white p-5 sm:p-7">
                @csrf
                @method('PUT')

                @include('customers.partials.form')

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-rule pt-5 sm:flex-row">
                    <x-button class="sm:min-w-44" loading="جارٍ الحفظ…">حفظ التعديلات</x-button>
                    <a href="{{ route('customers.index') }}" class="inline-flex h-13 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</a>
                </div>
            </form>

            <aside class="rounded-2xl border border-rule bg-white p-6">
                <h2 class="font-extrabold">حذف الزبون</h2>

                @if ($customer->canBeDeleted())
                    <p class="mt-3 text-sm leading-relaxed text-ink-muted">ليس له أي حركات بعد، لذلك يمكن حذفه إن أُضيف بالخطأ.</p>

                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="mt-4" data-submit-lock
                        data-confirm-title="حذف {{ $customer->name }}؟"
                        data-confirm="سيُحذف الزبون نهائياً. لا يمكن التراجع عن ذلك."
                        data-confirm-action="حذف نهائي">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-debit/30 text-sm font-bold text-debit transition hover:bg-debit-soft">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" /></svg>
                            حذف الزبون
                        </button>
                    </form>
                @else
                    <p class="mt-3 text-sm leading-relaxed text-ink-muted">له حركات مسجّلة، لذلك لا يمكن حذفه حتى يبقى حسابه كاملاً. يمكنك تعديل اسمه إن كان خاطئاً.</p>
                @endif
            </aside>
        </div>
    </div>
@endsection
