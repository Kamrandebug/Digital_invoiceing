<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExcelColumnService
{
    /**
     * @return array<int, array{column_label: string, db_column_name: string, sequence_no: int, is_visible: int, excel_width: int, alignment: string}>
     */
    protected function defaultColumnDefinitions(): array
    {
        return [
            ['column_label' => 'Invoice No', 'db_column_name' => 'invoice_no', 'sequence_no' => 1, 'is_visible' => 1, 'excel_width' => 18, 'alignment' => 'left'],
            ['column_label' => 'Date', 'db_column_name' => 'invoice_date', 'sequence_no' => 2, 'is_visible' => 1, 'excel_width' => 14, 'alignment' => 'center'],
            ['column_label' => 'Customer', 'db_column_name' => 'supplier_name', 'sequence_no' => 3, 'is_visible' => 1, 'excel_width' => 25, 'alignment' => 'left'],
            ['column_label' => 'Product', 'db_column_name' => 'product_name', 'sequence_no' => 4, 'is_visible' => 1, 'excel_width' => 25, 'alignment' => 'left'],
            ['column_label' => 'Qty', 'db_column_name' => 'qty', 'sequence_no' => 5, 'is_visible' => 1, 'excel_width' => 12, 'alignment' => 'right'],
            ['column_label' => 'Rate', 'db_column_name' => 'rate', 'sequence_no' => 6, 'is_visible' => 1, 'excel_width' => 14, 'alignment' => 'right'],
            ['column_label' => 'Amount', 'db_column_name' => 'amount', 'sequence_no' => 7, 'is_visible' => 1, 'excel_width' => 16, 'alignment' => 'right'],
            ['column_label' => 'Remarks', 'db_column_name' => 'remarks', 'sequence_no' => 8, 'is_visible' => 1, 'excel_width' => 20, 'alignment' => 'left'],
        ];
    }

    public function ensureDefaultColumns(): void
    {
        $conn = DB::connection('tenant');

        if (! $conn->getSchemaBuilder()->hasTable('purchase_breakup_excel_columns')) {
            return;
        }

        if ($conn->table('purchase_breakup_excel_columns')->count() === 0) {
            foreach ($this->defaultColumnDefinitions() as $col) {
                $conn->table('purchase_breakup_excel_columns')->insert($col);
            }
        }

        $conn->table('purchase_breakup_excel_columns')
            ->where('db_column_name', 'supplier_name')
            ->update(['column_label' => 'Customer']);
    }

    /**
     * @return Collection<int, object>
     */
    public function getVisibleColumns(): Collection
    {
        $this->ensureDefaultColumns();

        $columns = DB::connection('tenant')
            ->table('purchase_breakup_excel_columns')
            ->where('is_visible', 1)
            ->orderBy('sequence_no')
            ->get();

        if ($columns->isNotEmpty()) {
            return $columns;
        }

        return collect($this->defaultColumnDefinitions())->map(fn (array $col) => (object) $col);
    }

    /**
     * @return array<int, string>
     */
    public function getSelectColumnNames(): array
    {
        return $this->getVisibleColumns()
            ->pluck('db_column_name')
            ->filter(fn ($name) => preg_match('/^[a-z_][a-z0-9_]*$/i', (string) $name))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getHeadings(): array
    {
        return $this->getVisibleColumns()
            ->pluck('column_label')
            ->all();
    }

    public function getBreakupData(int $purchaseId, ?int $jobId = null, ?int $limit = null): Collection
    {
        $columns = $this->getSelectColumnNames();
        if ($columns === []) {
            return collect();
        }

        $query = DB::connection('tenant')
            ->table('purchase_breakup_invoice_data')
            ->select($columns)
            ->where('purchase_id', $purchaseId);

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        } else {
            $latestJobId = DB::connection('tenant')
                ->table('purchase_breakup_jobs')
                ->where('purchase_id', $purchaseId)
                ->orderByDesc('version_no')
                ->value('id');
            if ($latestJobId) {
                $query->where('job_id', $latestJobId);
            }
        }

        $query->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
