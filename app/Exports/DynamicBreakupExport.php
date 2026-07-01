<?php

namespace App\Exports;

use App\Services\ExcelColumnService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DynamicBreakupExport implements FromArray, WithHeadings, WithColumnWidths
{
    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, object>  $visibleColumns  Same collection used for preview (sequence_no order)
     */
    public function __construct(
        protected Collection $rows,
        protected Collection $visibleColumns,
        protected ExcelColumnService $columnService
    ) {}

    public function array(): array
    {
        return $this->rows->map(function ($row) {
            $line = [];
            foreach ($this->visibleColumns as $col) {
                $key = (string) $col->db_column_name;
                $value = is_array($row)
                    ? ($row[$key] ?? '')
                    : ($row->{$key} ?? '');

                if ($key === 'invoice_date' && $value) {
                    $value = \Carbon\Carbon::parse($value)->format('d/m/Y');
                } elseif ($this->columnService->isNumericBreakupColumn($col) && $value !== '' && is_numeric($value)) {
                    $value = round((float) $value, 2);
                }

                $line[] = $value;
            }

            return $line;
        })->all();
    }

    public function headings(): array
    {
        return $this->visibleColumns
            ->pluck('column_label')
            ->all();
    }

    /**
     * Column widths from purchase_breakup_excel_columns.excel_width (same basis as preview).
     *
     * @return array<string, float>
     */
    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->visibleColumns->values() as $index => $col) {
            $letter = $this->columnLetter($index);
            $widths[$letter] = max(8, (float) ($col->excel_width ?? 14));
        }

        return $widths;
    }

    protected function columnLetter(int $index): string
    {
        $letter = '';
        $n = $index + 1;
        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)).$letter;
            $n = intdiv($n, 26);
        }

        return $letter;
    }
}
