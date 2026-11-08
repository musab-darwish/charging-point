<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The debtors list as an Excel file: one sheet for who owes (highest first),
 * one for who paid ahead, each with a total at the bottom. The numbers come
 * straight from Debtors, so the file matches the page exactly.
 */
final class DebtorsExport
{
    /** The brand's paper, debit and credit colors (the file can't read the CSS tokens). */
    private const PAPER = 'E8E3D7';

    private const DEBIT = 'B3261E';

    private const CREDIT = '1F4FB0';

    public function __construct(private readonly Debtors $debtors) {}

    public function fileName(): string
    {
        return 'مديونون-'.CarbonImmutable::today()->format('Y-m-d').'.xlsx';
    }

    /**
     * Writes the workbook to the given path.
     */
    public function writeTo(string $path): void
    {
        $writer = new Writer;
        $writer->openToFile($path);

        $this->writeSheet($writer->getCurrentSheet(), $writer, 'عليهم', 'عليه (₪)', $this->debtors->owing, $this->debtors->totalOwing, self::DEBIT);
        $this->writeSheet($writer->addNewSheetAndMakeItCurrent(), $writer, 'لهم رصيد', 'له (₪)', $this->debtors->ahead, $this->debtors->totalAhead, self::CREDIT);

        // Open on the first sheet.
        $writer->setCurrentSheet($writer->getSheets()[0]);
        $writer->close();
    }

    /**
     * @param  iterable<DebtorRow>  $rows
     */
    private function writeSheet(Sheet $sheet, Writer $writer, string $name, string $balanceTitle, iterable $rows, string $total, string $color): void
    {
        $sheet->setName($name);
        $sheet->setSheetView((new SheetView)->withRightToLeft(true)->withFreezeRow(2));
        // In column order: Excel wants the widths listed left to right, or it offers to "repair" the file.
        $sheet->setColumnWidth(28, 1);
        $sheet->setColumnWidth(16, 2);
        $sheet->setColumnWidth(14, 3, 4);
        $sheet->setColumnWidth(16, 5);
        $sheet->setColumnWidth(12, 6);

        $header = (new Style)->withFontBold(true)->withBackgroundColor(self::PAPER);
        $money = (new Style)->withFormat('0.00');
        $balance = (new Style)->withFormat('0.00')->withFontBold(true)->withFontColor($color);
        $date = (new Style)->withFormat('yyyy-mm-dd');

        $writer->addRow(Row::fromValuesWithStyle(
            ['الاسم', 'الجوال', $balanceTitle, 'آخر دفعة (₪)', 'تاريخ آخر دفعة', 'منذ (يوم)'],
            $header,
        ));

        foreach ($rows as $row) {
            $payment = $row->lastPayment;

            $writer->addRow(new Row([
                Cell::fromValue($row->customer->name),
                Cell::fromValue($row->customer->phone_display ?? ''),
                Cell::fromValue($this->amount($row->balance), $balance),
                $payment ? Cell::fromValue($this->amount($payment->amount), $money) : Cell::fromValue('لم يدفع'),
                $payment ? Cell::fromValue(CarbonImmutable::parse($payment->occurred_at)->startOfDay(), $date) : Cell::fromValue(''),
                Cell::fromValue($row->daysSincePayment() ?? ''),
            ]));
        }

        $writer->addRow(new Row([
            Cell::fromValue('المجموع', $header),
            Cell::fromValue('', $header),
            Cell::fromValue($this->amount($total), (new Style)->withFormat('0.00')->withFontBold(true)->withFontColor($color)->withBackgroundColor(self::PAPER)),
            Cell::fromValue('', $header),
            Cell::fromValue('', $header),
            Cell::fromValue('', $header),
        ]));
    }

    /**
     * A stored amount ("47.50", or "-8.00" for a balance in their favour) as a
     * positive number Excel can add up.
     */
    private function amount(string $amount): float
    {
        return (float) ltrim($amount, '-');
    }
}
