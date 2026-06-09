<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExcelColumnService
{
    /** @var array<int, string>|null */
    protected static ?array $invoiceDataColumnsCache = null;

    /** @var array<int, string>|null */
    protected static ?array $purchaseDetailColumnsCache = null;

    /** @var array<string, mixed> */
    protected array $lastExportDebug = [];

    public static function clearSchemaCaches(): void
    {
        self::$invoiceDataColumnsCache = null;
        self::$purchaseDetailColumnsCache = null;
    }

    /**
     * Columns physically stored on purchase_breakup_invoice_data.
     *
     * @return array<int, string>
     */
    public function invoiceDataColumnNames(): array
    {
        if (self::$invoiceDataColumnsCache !== null) {
            return self::$invoiceDataColumnsCache;
        }

        if (! Schema::connection('tenant')->hasTable('purchase_breakup_invoice_data')) {
            return self::$invoiceDataColumnsCache = [];
        }

        $skip = ['id', 'purchase_id', 'job_id', 'created_at'];

        self::$invoiceDataColumnsCache = array_values(array_diff(
            Schema::connection('tenant')->getColumnListing('purchase_breakup_invoice_data'),
            $skip
        ));

        return self::$invoiceDataColumnsCache;
    }

    /**
     * @return array<int, string>
     */
    protected function purchaseDetailColumnNames(): array
    {
        if (self::$purchaseDetailColumnsCache !== null) {
            return self::$purchaseDetailColumnsCache;
        }

        if (! Schema::connection('tenant')->hasTable('tbl_purchase_detail')) {
            return self::$purchaseDetailColumnsCache = [];
        }

        self::$purchaseDetailColumnsCache = Schema::connection('tenant')->getColumnListing('tbl_purchase_detail');

        return self::$purchaseDetailColumnsCache;
    }

    /**
     * Seed rows when purchase_breakup_excel_columns is empty (first install only).
     *
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

        if (! Schema::connection('tenant')->hasTable('purchase_breakup_excel_columns')) {
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
     * Visible export/preview columns — always from purchase_breakup_excel_columns.
     *
     * @return Collection<int, object>
     */
    public function getVisibleColumns(): Collection
    {
        $this->ensureDefaultColumns();

        $columns = DB::connection('tenant')
            ->table('purchase_breakup_excel_columns')
            ->where('is_visible', 1)
            ->orderBy('sequence_no')
            ->orderBy('id')
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
        return $this->configuredColumnNamesFrom($this->getVisibleColumns());
    }

    /**
     * Column db names in purchase_breakup_excel_columns order (sequence_no, is_visible = 1).
     *
     * @param  Collection<int, object>  $visibleColumns
     * @return array<int, string>
     */
    protected function configuredColumnNamesFrom(Collection $visibleColumns): array
    {
        $names = [];
        foreach ($visibleColumns as $col) {
            $name = trim((string) ($col->db_column_name ?? ''));
            if ($name !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                $names[] = $name;
            }
        }

        return $names;
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

    /**
     * Whether a configured breakup column should be formatted as numeric (preview + Excel).
     */
    public function isNumericBreakupColumn(object $col): bool
    {
        if (($col->alignment ?? 'left') === 'right') {
            return true;
        }

        $name = strtolower((string) ($col->db_column_name ?? ''));

        return in_array($name, [
            'qty', 'rate', 'amount',
            'gst_percentage', 'gstpercentage', 'gst_amount', 'gstamount',
            'discount_percentage', 'discountpercentage', 'discount_amount', 'discountamount',
            'extra_tax', 'extratax', 'further_tax', 'furthertax',
            'fed_payable', 'fedpayable', 'total_values', 'totalvalues',
            'valuesalesexcludingst', 'salestaxapplicable', 'salestaxwithheldatsource',
            'fixednotifiedvalueorretailprice', 'tp_rate', 'tprate',
        ], true);
    }

    /**
     * Single payload for preview and Excel — same columns and row projection.
     *
     * @return array{columns: Collection<int, object>, rows: Collection<int, object>, debug: array<string, mixed>}
     */
    public function buildExportPackage(int $purchaseId, ?int $jobId = null, ?int $limit = null): array
    {
        $visibleColumns = $this->getVisibleColumns();
        $rows = $this->getBreakupData($purchaseId, $jobId, $limit, $visibleColumns);

        return [
            'columns' => $visibleColumns,
            'rows' => $rows,
            'debug' => $this->lastExportDebug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastExportDebug(): array
    {
        return $this->lastExportDebug;
    }

    public function getBreakupData(
        int $purchaseId,
        ?int $jobId = null,
        ?int $limit = null,
        ?Collection $visibleColumns = null
    ): Collection {
        $visibleColumns ??= $this->getVisibleColumns();
        $configuredColumns = $this->configuredColumnNamesFrom($visibleColumns);
        $this->lastExportDebug = [
            'column_config_sql' => 'SELECT column_label, db_column_name, sequence_no, is_visible, excel_width, alignment'
                .' FROM purchase_breakup_excel_columns WHERE is_visible = 1 ORDER BY sequence_no ASC, id ASC',
            'visible_columns' => $visibleColumns->map(fn ($col) => [
                'column_label' => $col->column_label ?? '',
                'db_column_name' => $col->db_column_name ?? '',
                'sequence_no' => $col->sequence_no ?? null,
                'is_visible' => $col->is_visible ?? null,
            ])->values()->all(),
            'configured_db_columns' => $configuredColumns,
        ];

        if ($configuredColumns === []) {
            return collect();
        }

        [$invoiceSelectColumns, $detailColumns, $invoiceColumnMap] = $this->splitConfiguredColumns($configuredColumns);

        $query = DB::connection('tenant')
            ->table('purchase_breakup_invoice_data')
            ->where('purchase_id', $purchaseId);

        if ($invoiceSelectColumns !== []) {
            $query->select($invoiceSelectColumns);
        } else {
            $query->selectRaw('1 as _row');
        }

        $resolvedJobId = $jobId;
        if ($resolvedJobId === null) {
            $resolvedJobId = DB::connection('tenant')
                ->table('purchase_breakup_jobs')
                ->where('purchase_id', $purchaseId)
                ->orderByDesc('version_no')
                ->value('id');
        }

        if ($resolvedJobId !== null) {
            $query->where('job_id', $resolvedJobId);
        }

        $query->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $this->lastExportDebug['invoice_data_sql'] = $query->toSql();
        $this->lastExportDebug['invoice_data_bindings'] = $query->getBindings();
        $this->lastExportDebug['job_id'] = $resolvedJobId;
        $this->lastExportDebug['invoice_columns'] = $invoiceSelectColumns;
        $this->lastExportDebug['detail_columns'] = $detailColumns;

        $rows = $query->get();

        if ($detailColumns === [] || $rows->isEmpty()) {
            return $this->projectRowsToConfiguredColumns(
                $rows,
                $configuredColumns,
                $invoiceColumnMap,
                $detailColumns
            );
        }

        $detailQuery = $this->buildPurchaseDetailQuery($purchaseId, $detailColumns);
        $this->lastExportDebug['purchase_detail_sql'] = $detailQuery['sql'];
        $this->lastExportDebug['purchase_detail_bindings'] = $detailQuery['bindings'];

        $detail = $detailQuery['row'];
        if (! $detail) {
            return $this->projectRowsToConfiguredColumns(
                $rows,
                $configuredColumns,
                $invoiceColumnMap,
                $detailColumns
            );
        }

        $enriched = $this->enrichRowsWithPurchaseDetail($rows, $detail, $detailColumns);

        return $this->projectRowsToConfiguredColumns(
            $enriched,
            $configuredColumns,
            $invoiceColumnMap,
            $detailColumns
        );
    }

    /**
     * @param  array<int, string>  $configuredColumns
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<string, string>}
     */
    protected function splitConfiguredColumns(array $configuredColumns): array
    {
        $invoiceTableColumns = $this->invoiceDataColumnNames();
        $invoiceLookup = [];
        foreach ($invoiceTableColumns as $column) {
            $invoiceLookup[strtolower($column)] = $column;
        }

        $invoiceSelectColumns = [];
        $detailColumns = [];
        $invoiceColumnMap = [];

        foreach ($configuredColumns as $name) {
            $actual = $invoiceLookup[strtolower($name)] ?? null;
            if ($actual !== null) {
                if (! in_array($actual, $invoiceSelectColumns, true)) {
                    $invoiceSelectColumns[] = $actual;
                }
                $invoiceColumnMap[$name] = $actual;
            } else {
                $detailColumns[] = $name;
            }
        }

        return [$invoiceSelectColumns, $detailColumns, $invoiceColumnMap];
    }

    /**
     * @param  array<int, string>  $detailColumns
     * @return array{sql: string, bindings: array<int, mixed>, row: ?object}
     */
    protected function buildPurchaseDetailQuery(int $purchaseId, array $detailColumns): array
    {
        if (! Schema::connection('tenant')->hasTable('tbl_purchase_detail')) {
            return ['sql' => null, 'bindings' => [], 'row' => null];
        }

        $detailFields = ['Amount', 'Quantity', 'Rate'];
        foreach ($detailColumns as $dbColumnName) {
            $field = $this->resolvePurchaseDetailField($dbColumnName);
            if ($field && ! in_array($field, $detailFields, true)) {
                $detailFields[] = $field;
            }
        }

        $query = DB::connection('tenant')
            ->table('tbl_purchase_detail')
            ->where('PurchaseID', $purchaseId)
            ->where('Status', '!=', 2)
            ->orderBy('ID')
            ->select($detailFields)
            ->limit(1);

        return [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'row' => $query->first(),
        ];
    }

    /**
     * Load purchase line fields required by configured excel columns.
     *
     * @param  array<int, string>  $detailColumns
     */
    public function loadPurchaseDetailForColumns(int $purchaseId, array $detailColumns): ?object
    {
        return $this->buildPurchaseDetailQuery($purchaseId, $detailColumns)['row'];
    }

    /**
     * Map purchase_breakup_excel_columns.db_column_name → tbl_purchase_detail column.
     */
    protected function resolvePurchaseDetailField(string $dbColumnName): ?string
    {
        $available = $this->purchaseDetailColumnNames();
        $availableLower = array_change_key_case(array_flip($available), CASE_LOWER);

        $candidates = [
            $dbColumnName,
            $this->snakeToPascal($dbColumnName),
            $this->detailColumnAliases()[$dbColumnName] ?? null,
            $this->detailColumnAliases()[strtolower($dbColumnName)] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }
            if (in_array($candidate, $available, true)) {
                return $candidate;
            }
            $lc = strtolower($candidate);
            if (isset($availableLower[$lc])) {
                return $available[$availableLower[$lc]];
            }
        }

        return null;
    }

    /**
     * Optional aliases when excel column names differ from DB field names.
     *
     * @return array<string, string>
     */
    protected function detailColumnAliases(): array
    {
        return [
            'hs_code' => 'HsCode',
            'hscode' => 'HsCode',
            'product_description' => 'ProductDescription',
            'description' => 'ProductDescription',
            'sale_type' => 'SaleTypeDesc',
            'saletype' => 'SaleTypeDesc',
            'sale_type_desc' => 'SaleTypeDesc',
            'sale_type_to_rate' => 'SaleTypeToRateDesc',
            'saletypetorate' => 'SaleTypeToRateDesc',
            'sale_type_to_rate_desc' => 'SaleTypeToRateDesc',
            'sro_schedule' => 'SroScheduleDesc',
            'sro_schedule_desc' => 'SroScheduleDesc',
            'sro_item' => 'SroItemSerialNo',
            'sro_item_serial_no' => 'SroItemSerialNo',
            'gst_percentage' => 'GstPercentage',
            'gst_amount' => 'GSTAmount',
            'discount_percentage' => 'DiscountPercentage',
            'discount_amount' => 'DiscountAmount',
            'extra_tax' => 'ExtraTax',
            'further_tax' => 'FurtherTax',
            'fed_payable' => 'FedPayable',
            'total_values' => 'TotalValues',
            'total_inc_tax' => 'TotalValues',
            'value_sales_excluding_st' => 'ValueSalesExcludingST',
            'sales_tax_applicable' => 'SalesTaxApplicable',
            'sales_tax_withheld_at_source' => 'SalesTaxWithheldAtSource',
            'fixed_notified_value_or_retail_price' => 'FixedNotifiedValueOrRetailPrice',
            'rate_type' => 'RateType',
            'tp_rate' => 'TPRate',
            'uom_id' => 'UomID',
        ];
    }

    protected function snakeToPascal(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($value))));
    }

    protected function shouldScaleDetailField(string $detailField): bool
    {
        return in_array($detailField, [
            'Amount',
            'GSTAmount',
            'DiscountAmount',
            'ExtraTax',
            'FurtherTax',
            'FedPayable',
            'TotalValues',
            'ValueSalesExcludingST',
            'SalesTaxApplicable',
            'SalesTaxWithheldAtSource',
            'FixedNotifiedValueOrRetailPrice',
        ], true);
    }

    protected function isPercentageDetailField(string $detailField): bool
    {
        return in_array($detailField, [
            'GstPercentage',
            'DiscountPercentage',
        ], true);
    }

    /**
     * @param  array<int, string>  $detailColumns
     */
    protected function enrichRowsWithPurchaseDetail(Collection $rows, object $detail, array $detailColumns): Collection
    {
        $lineQty = (float) ($detail->Quantity ?? 0);
        $lineRate = (float) ($detail->Rate ?? 0);
        $lineAmount = (float) ($detail->Amount ?? 0);
        if ($lineAmount <= 0 && $lineQty > 0 && $lineRate > 0) {
            $lineAmount = round($lineQty * $lineRate, 2);
        }

        return $rows->map(function ($row) use ($detail, $detailColumns, $lineAmount) {
            $rowAmount = (float) ($row->amount ?? 0);
            $factor = ($lineAmount > 0) ? ($rowAmount / $lineAmount) : 1.0;

            foreach ($detailColumns as $dbColumnName) {
                $detailField = $this->resolvePurchaseDetailField($dbColumnName);
                if (! $detailField) {
                    $row->{$dbColumnName} = '';

                    continue;
                }

                $raw = $detail->{$detailField} ?? null;

                if ($this->shouldScaleDetailField($detailField)) {
                    $row->{$dbColumnName} = round(((float) $raw) * $factor, 2);
                } elseif ($this->isPercentageDetailField($detailField)) {
                    $row->{$dbColumnName} = $raw !== null && $raw !== '' ? round((float) $raw, 2) : '';
                } else {
                    $row->{$dbColumnName} = $raw ?? '';
                }
            }

            return $row;
        });
    }

    /**
     * Return only keys defined in purchase_breakup_excel_columns, in configured order.
     *
     * @param  array<int, string>  $configuredColumns
     * @param  array<string, string>  $invoiceColumnMap configured db_column_name => invoice_data column
     * @param  array<int, string>  $detailColumns
     */
    protected function projectRowsToConfiguredColumns(
        Collection $rows,
        array $configuredColumns,
        array $invoiceColumnMap,
        array $detailColumns
    ): Collection {
        return $rows->map(function ($row) use ($configuredColumns, $invoiceColumnMap, $detailColumns) {
            $projected = new \stdClass;
            foreach ($configuredColumns as $column) {
                if (isset($invoiceColumnMap[$column])) {
                    $sourceKey = $invoiceColumnMap[$column];
                    $projected->{$column} = $row->{$sourceKey} ?? '';
                } elseif (in_array($column, $detailColumns, true)) {
                    $projected->{$column} = $row->{$column} ?? '';
                } else {
                    $projected->{$column} = '';
                }
            }

            return $projected;
        });
    }
}
