<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    Carbon::setTestNow('2026-11-08 12:00:00');
    $this->owner = User::factory()->owner()->create();
});

afterEach(fn () => Carbon::setTestNow());

/**
 * Downloads the export and reads it back: [sheet name => rows of cell values].
 *
 * @return array<string, list<list<mixed>>>
 */
function downloadDebtorsFile($test): array
{
    $response = $test->actingAs($test->owner)->get(route('debtors.export'))->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();

    $reader = new Reader;
    $reader->open($path);

    $sheets = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $sheets[$sheet->getName()][] = array_map(
                fn ($value) => $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value,
                $row->toArray(),
            );
        }
    }
    $reader->close();
    unlink($path);

    return $sheets;
}

test('the owner downloads the debtors as an Excel file named by today', function () {
    Transaction::factory()->for(Customer::factory())->debit('5.00')->create();

    $response = $this->actingAs($this->owner)->get(route('debtors.export'))->assertOk();

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain(rawurlencode('مديونون-2026-11-08.xlsx'));

    unlink($response->baseResponse->getFile()->getPathname());
});

test('those who owe come highest first, with phone, last payment and a total', function () {
    $big = Customer::factory()->create(['name' => 'كبير', 'phone' => '970599123456']);
    $small = Customer::factory()->withoutPhone()->create(['name' => 'صغير']);

    Transaction::factory()->for($big)->debit('40.00')->create(['occurred_at' => '2026-11-01 10:00:00']);
    Transaction::factory()->for($big)->credit('12.50')->create(['occurred_at' => '2026-11-05 18:30:00']);
    Transaction::factory()->for($small)->debit('5.00')->create();

    $owing = downloadDebtorsFile($this)['عليهم'];

    expect($owing)->toBe([
        ['الاسم', 'الجوال', 'عليه (₪)', 'آخر دفعة (₪)', 'تاريخ آخر دفعة', 'منذ (يوم)'],
        ['كبير', '0599 123 456', 27.5, 12.5, '2026-11-05', 3],
        ['صغير', '', 5, 'لم يدفع', '', ''],
        ['المجموع', '', 32.5, '', '', ''],
    ]);
});

test('those who paid ahead are on their own sheet, as a positive amount', function () {
    $ahead = Customer::factory()->create(['name' => 'دافع زيادة', 'phone' => '972599000111']);
    Transaction::factory()->for($ahead)->credit('8.00')->create(['occurred_at' => '2026-11-08 09:00:00']);
    Transaction::factory()->for($ahead)->debit('2.00')->create();

    $sheets = downloadDebtorsFile($this);

    expect(array_keys($sheets))->toBe(['عليهم', 'لهم رصيد'])
        ->and($sheets['عليهم'])->toHaveCount(2)
        ->and($sheets['لهم رصيد'])->toBe([
            ['الاسم', 'الجوال', 'له (₪)', 'آخر دفعة (₪)', 'تاريخ آخر دفعة', 'منذ (يوم)'],
            ['دافع زيادة', '0599 000 111', 6, 8, '2026-11-08', 0],
            ['المجموع', '', 6, '', '', ''],
        ]);
});

test('a cancelled payment is not the last payment in the file', function () {
    $customer = Customer::factory()->create(['name' => 'ألغيت دفعته']);
    Transaction::factory()->for($customer)->debit('10.00')->create();
    $payment = Transaction::factory()->for($customer)->credit('4.00')->create();
    Transaction::factory()->for($customer)->debit('4.00')->create(['reverses_id' => $payment->id, 'description' => 'سُجّلت بالخطأ']);

    $row = downloadDebtorsFile($this)['عليهم'][1];

    expect($row)->toBe(['ألغيت دفعته', $row[1], 10, 'لم يدفع', '', '']);
});

test('only the owner can download the file or see its button', function () {
    Transaction::factory()->for(Customer::factory())->debit('5.00')->create();
    $worker = User::factory()->create();

    $this->actingAs($worker)->get(route('debtors.export'))->assertForbidden();
    $this->actingAs($worker)->get(route('debtors.index'))->assertDontSee('تصدير Excel');
    $this->actingAs($this->owner)->get(route('debtors.index'))->assertSee('تصدير Excel');
});

test('the button is hidden while nobody owes or has money with us', function () {
    $this->actingAs($this->owner)->get(route('debtors.index'))->assertDontSee('تصدير Excel');
});
