<?php

use App\Support\DayStatus;

test('nothing paid yet', function () {
    $status = DayStatus::from('5.00', '0.00');

    expect($status->state)->toBe(DayStatus::UNPAID)
        ->and($status->remaining)->toBe('5.00')
        ->and($status->label())->toBe('لم يُدفع بعد');
});

test('paid exactly', function () {
    $status = DayStatus::from('5.00', '5.00');

    expect($status->state)->toBe(DayStatus::PAID)
        ->and($status->remaining)->toBe('0.00')
        ->and($status->label())->toBe('واصل');
});

test('paid part of it', function () {
    $status = DayStatus::from('5.00', '2.00');

    expect($status->state)->toBe(DayStatus::PARTIAL)
        ->and($status->remaining)->toBe('3.00')
        ->and($status->label())->toBe('متبقي 3.00 ₪');
});

test('paid more than today, the rest went to an old debt', function () {
    $status = DayStatus::from('5.00', '8.00');

    expect($status->state)->toBe(DayStatus::PAID)
        ->and($status->remaining)->toBe('0.00');
});

test('no work today, only paid an old debt', function () {
    $status = DayStatus::from('0.00', '10.00');

    expect($status->state)->toBe(DayStatus::PAID)
        ->and($status->remaining)->toBe('0.00');
});

test('decimal amounts do not leave float leftovers', function () {
    $status = DayStatus::from('1.10', '0.30');

    expect($status->state)->toBe(DayStatus::PARTIAL)
        ->and($status->remaining)->toBe('0.80');
});
