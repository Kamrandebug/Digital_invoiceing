<?php

namespace App\Exports;

use App\Services\ExcelColumnService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DynamicBreakupExport implements FromArray, WithHeadings
{
    public function __construct(
        protected Collection $rows,
        protected ExcelColumnService $columnService
    ) {}

    public function array(): array
    {
        $columns = $this->columnService->getSelectColumnNames();

        return $this->rows->map(function ($row) use ($columns) {
            $line = [];
            foreach ($columns as $col) {
                $value = is_array($row)
                    ? ($row[$col] ?? '')
                    : ($row->{$col} ?? '');

                if ($col === 'invoice_date' && $value) {
                    $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                }

                $line[] = $value;
            }

            return $line;
        })->all();
    }

    public function headings(): array
    {
        return $this->columnService->getHeadings();
    }
}
