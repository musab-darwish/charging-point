@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <h1 class="text-2xl font-bold">أهلاً بنقطة الشحن</h1>
    <p class="mt-2 text-gray-600">صفحة تجربة للقالب: الاتجاه، والخط، وTailwind.</p>

    <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between">
            <span>رصيد تجريبي</span>
            <span class="font-bold">25.50 ₪</span>
        </div>

        <p class="mt-3 border-s-4 border-amber-400 ps-3 text-sm text-gray-600">
            هاد الخط الأصفر لازم يطلع على اليمين.
        </p>
    </div>
@endsection
