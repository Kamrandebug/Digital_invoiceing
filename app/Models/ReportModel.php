<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_temp_reports_detail';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'RequestID',
        'ReportID',
        'CustomReportName',
        'PrintOrientation',
        'RowGroupColumnName',
        'ColumName',
        'CustomDisplayOrder',
        'CustomLabel',
        'CustomIsOn',
        'IsTotal',
        'CustomWidthMin',
        'CustomWidthMax',
        'CustomAlignment',
        'CustomColumnFormat',
        'ErrMsg',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn',
    ];

    /**
     * Quoted active tenant database name for cross-DB / set-based SQL statements.
     */
    public static function tenantDatabaseQuoted(): string
    {
        $tenantDb = session('selected_tenant.dbname') ?: config('database.connections.tenant.database');
        $tenantDb = trim((string) $tenantDb);

        if ($tenantDb === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
            throw new \RuntimeException('Invalid tenant database name in session.');
        }

        return '`' . str_replace('`', '``', $tenantDb) . '`';
    }

    /**
     * Ensure tenant report tables exist and default report definitions from `core_digital_invoicing` are synced.
     */
    public static function ensureTenantReportSchemaAndDefaults(?int $reportId = null): void
    {
        try {
            $tenantDbQuoted = self::tenantDatabaseQuoted();

            DB::connection('landlord')->statement("CREATE TABLE IF NOT EXISTS {$tenantDbQuoted}.`tbl_reports` LIKE `core_digital_invoicing`.`tbl_reports`");
            DB::connection('landlord')->statement("CREATE TABLE IF NOT EXISTS {$tenantDbQuoted}.`tbl_reports_detail` LIKE `core_digital_invoicing`.`tbl_reports_detail`");
            DB::connection('landlord')->statement("CREATE TABLE IF NOT EXISTS {$tenantDbQuoted}.`tbl_reports_parameters` LIKE `core_digital_invoicing`.`tbl_reports_parameters`");
            DB::connection('landlord')->statement("CREATE TABLE IF NOT EXISTS {$tenantDbQuoted}.`tbl_reports_parameters_print` LIKE `core_digital_invoicing`.`tbl_reports_parameters_print`");

            DB::connection('landlord')->statement("
                CREATE TABLE IF NOT EXISTS {$tenantDbQuoted}.`tbl_temp_reports_detail` (
                  `ID` int(11) NOT NULL AUTO_INCREMENT,
                  `RequestID` varchar(100) NOT NULL,
                  `ReportID` int(11) NOT NULL,
                  `CustomReportName` varchar(250) DEFAULT NULL,
                  `PrintOrientation` enum('Portrait','Landscape') DEFAULT 'Portrait',
                  `RowGroupColumnName` varchar(250) DEFAULT NULL,
                  `ColumName` varchar(100) NOT NULL,
                  `CustomDisplayOrder` int(11) DEFAULT NULL,
                  `CustomLabel` varchar(100) DEFAULT NULL,
                  `CustomIsOn` int(1) DEFAULT 1,
                  `IsTotal` tinyint(1) DEFAULT 0,
                  `CustomWidthMin` int(3) DEFAULT NULL,
                  `CustomWidthMax` int(3) DEFAULT NULL,
                  `CustomAlignment` enum('left','right','center') DEFAULT 'left',
                  `CustomColumnFormat` varchar(20) DEFAULT NULL,
                  `ErrMsg` varchar(500) DEFAULT NULL,
                  `CreatedBy` int(11) DEFAULT NULL,
                  `CreatedOn` datetime DEFAULT current_timestamp(),
                  `UpdatedBy` int(11) DEFAULT NULL,
                  `UpdatedOn` datetime DEFAULT NULL,
                  PRIMARY KEY (`ID`),
                  KEY `Idx_RequestID` (`RequestID`),
                  KEY `Idx_ReportID_Col` (`ReportID`, `ColumName`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");

            // Ensure Sale Report module row exists with IsReport = 1 in tbl_module
            DB::connection('landlord')->statement("
                INSERT INTO {$tenantDbQuoted}.`tbl_module`
                (`ID`, `Name`, `Description`, `CreatedOn`, `CreatedBy`, `Status`, `Level`, `CurrentPage`, `MainModuleID`, `ModuleURL`, `MainModuleIcon`, `IsReport`, `IsGlobalSetting`, `DisplayOrder`)
                VALUES
                (10, 'Sale Report', 'Detailed line-level sales and FBR tax report', NOW(), 1, 1, 1, 'sale-report', 4, '/reports/10', 'ri-file-chart-line', 1, 0, 1)
                ON DUPLICATE KEY UPDATE
                  `IsReport` = 1,
                  `MainModuleID` = 4,
                  `ModuleURL` = '/reports/10'
            ");

            // Copy any missing master reports from core_digital_invoicing into tenant DB
            $whereSql = $reportId !== null ? ' WHERE `ReportID` = ' . (int) $reportId : '';
            DB::connection('landlord')->statement("INSERT IGNORE INTO {$tenantDbQuoted}.`tbl_reports` SELECT * FROM `core_digital_invoicing`.`tbl_reports`{$whereSql}");
            DB::connection('landlord')->statement("INSERT IGNORE INTO {$tenantDbQuoted}.`tbl_reports_detail` SELECT * FROM `core_digital_invoicing`.`tbl_reports_detail`{$whereSql}");
            DB::connection('landlord')->statement("INSERT IGNORE INTO {$tenantDbQuoted}.`tbl_reports_parameters` SELECT * FROM `core_digital_invoicing`.`tbl_reports_parameters`{$whereSql}");
            DB::connection('landlord')->statement("INSERT IGNORE INTO {$tenantDbQuoted}.`tbl_reports_parameters_print` SELECT * FROM `core_digital_invoicing`.`tbl_reports_parameters_print`{$whereSql}");
        } catch (\Throwable $e) {
            \Log::warning('ReportModel::ensureTenantReportSchemaAndDefaults warning: ' . $e->getMessage());
        }
    }

    /**
     * Get all permitted reports (`tbl_module.IsReport = 1`) joined with `tbl_reports`.
     */
    public static function getPermittedReports(): Collection
    {
        self::ensureTenantReportSchemaAndDefaults();

        $sidebarPermissions = session('selected_tenant.permissions', []);
        $isOwnerOrNoRole    = empty($sidebarPermissions);

        $modules = DB::connection('tenant')->table('tbl_module as m')
            ->leftJoin('tbl_reports as r', 'r.ReportID', '=', 'm.ID')
            ->where('m.Status', 1)
            ->where('m.IsReport', 1)
            ->orderBy('m.DisplayOrder')
            ->orderBy('m.ID')
            ->select(
                'm.ID as ModuleID',
                'm.Name as ModuleName',
                'm.Description',
                'm.CurrentPage',
                'm.ModuleURL',
                'm.MainModuleIcon',
                'r.ReportID',
                'r.ReportName',
                'r.CustomName',
                'r.ReportType',
                'r.PrintOrientation',
                'r.RowGroupColumnName'
            )
            ->get();

        return $modules->filter(function ($mod) use ($sidebarPermissions, $isOwnerOrNoRole) {
            if ($isOwnerOrNoRole) {
                return true;
            }
            if (($sidebarPermissions[$mod->ModuleName]['ViewOnly'] ?? 0) == 1) {
                return true;
            }
            // Fallback: if Sale Report was just added and user has Invoices ViewOnly access
            if ($mod->ModuleName === 'Sale Report' && ($sidebarPermissions['Invoices']['ViewOnly'] ?? 0) == 1) {
                return true;
            }
            return false;
        })->values();
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  REUSABLE PARAMETER LOADER FUNCTIONS (Called via tbl_reports_parameters.functionName)
    // ══════════════════════════════════════════════════════════════════════════

    public static function load_customer(): array
    {
        $rows = DB::connection('tenant')->select(InvoiceModel::Load_Customer());
        $options = [];
        foreach ($rows as $c) {
            $label = $c->DisplayName ?? trim(($c->FirstName ?? '') . ' ' . ($c->LastName ?? ''));
            $options[] = [
                'id'    => (string) $c->ID,
                'label' => $label ?: ('Customer #' . $c->ID),
            ];
        }
        return $options;
    }

    public static function load_product(): array
    {
        $rows = DB::connection('tenant')->select(InvoiceModel::Load_Product());
        $options = [];
        foreach ($rows as $p) {
            $options[] = [
                'id'    => (string) $p->ID,
                'label' => $p->ProductName ?? $p->Name ?? ('Product #' . $p->ID),
            ];
        }
        return $options;
    }

    public static function load_invoice_status(): array
    {
        return [
            ['id' => '',               'label' => 'Active (excl. archived)'],
            ['id' => 'active',         'label' => 'Status = Active'],
            ['id' => 'not_posted_fbr', 'label' => 'Draft (not posted to FBR)'],
            ['id' => 'posted_fbr',     'label' => 'Posted to FBR'],
            ['id' => 'archived',       'label' => 'Archived'],
        ];
    }

    /**
     * Resolve parameter metadata + dropdown options dynamically from `tbl_reports_parameters`.
     */
    public static function getReportParameters(int $reportId): Collection
    {
        $params = DB::connection('tenant')->table('tbl_reports_parameters')
            ->where('ReportID', $reportId)
            ->where('ParameterIsON', 1)
            ->orderBy('DisplayOrder')
            ->orderBy('id')
            ->get();

        return $params->map(function ($param) {
            $fn = trim((string) ($param->functionName ?? ''));
            $param->controlType = 'text';
            $param->options     = [];

            if (strtolower($fn) === 'daterange' || strtolower($fn) === 'date' || str_contains(strtolower((string) $param->Parameter), 'date')) {
                $param->controlType = 'date';
            } elseif ($fn !== '' && method_exists(self::class, $fn)) {
                $param->controlType = 'select';
                $param->options     = self::$fn();
            }

            return $param;
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  DYNAMIC REPORT DATA FUNCTIONS (Called via tbl_reports.ReportFunction)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Sale Report dataset generator (ReportID = 10, ReportFunction = 'get_sale_report')
     */
    public static function get_sale_report(array $filters = []): Collection
    {
        $q = DB::connection('tenant')->table('tbl_sales_invoice_detail as d')
            ->join('tbl_sales_invoice_header as h', 'd.InvoiceID', '=', 'h.ID')
            ->leftJoin('tbl_customer as cust', 'h.CustomerID', '=', 'cust.ID')
            ->leftJoin('tbl_product as p', 'd.ProductID', '=', 'p.ID')
            ->select(
                'h.ID as header_id',
                'h.DocNo',
                'h.InvoiceDate',
                'h.Status as header_status',
                'h.FbrTransactionID',
                'h.SubTotal as header_sub_total',
                'h.Total as header_total',
                'd.Quantity',
                'd.Rate',
                'd.Amount',
                'd.SalesTaxApplicable as GSTAmount',
                'd.Discount as DiscountAmount',
                'd.ExtraTax',
                'd.FurtherTax',
                'd.FedPayable',
                'd.TotalValues',
                'd.HsCode',
                'd.ProductDescription as line_description',
                'd.Uom_Desc',
                'p.Name as product_name',
                'p.ID as product_id',
                'h.CustomerID',
                InvoiceModel::sqlCustomerDisplayName()
            )
            ->where(function ($w) {
                $w->whereNull('d.Status')->orWhere('d.Status', '!=', 2);
            });

        $statusFilter = (string) ($filters['invoice_status'] ?? '');

        if ($statusFilter === 'archived') {
            $q->where('h.Status', 2);
        } elseif ($statusFilter === 'posted_fbr') {
            $q->where('h.Status', '!=', 2)
              ->whereRaw("TRIM(IFNULL(h.FbrTransactionID, '')) <> ''");
        } elseif ($statusFilter === 'not_posted_fbr') {
            $q->where('h.Status', '!=', 2)
              ->whereRaw("TRIM(IFNULL(h.FbrTransactionID, '')) = ''");
        } elseif ($statusFilter === 'active') {
            $q->where('h.Status', 1);
        } else {
            $q->where(function ($w) {
                $w->whereNull('h.Status')->orWhere('h.Status', '!=', 2);
            });
        }

        if (! empty($filters['customer_id'])) {
            if (is_array($filters['customer_id'])) {
                $q->whereIn('h.CustomerID', array_map('intval', $filters['customer_id']));
            } else {
                $q->where('h.CustomerID', (int) $filters['customer_id']);
            }
        }

        if (! empty($filters['product_id'])) {
            if (is_array($filters['product_id'])) {
                $q->whereIn('d.ProductID', array_map('intval', $filters['product_id']));
            } else {
                $q->where('d.ProductID', (int) $filters['product_id']);
            }
        }

        if (! empty($filters['start_date'])) {
            $q->whereDate('h.InvoiceDate', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $q->whereDate('h.InvoiceDate', '<=', $filters['end_date']);
        }

        $rows = $q->orderByDesc('h.InvoiceDate')->orderBy('h.DocNo')->orderBy('d.ID')->get();

        return $rows->map(function ($r) {
            $st = (int) ($r->header_status ?? 0);
            if ($st === 2) {
                $r->InvoiceStatus = 'Archived';
            } else {
                $fbr = trim((string) ($r->FbrTransactionID ?? ''));
                $r->InvoiceStatus = $fbr !== '' ? 'Posted to FBR' : 'Draft (not posted to FBR)';
            }

            if ($r->InvoiceDate) {
                try {
                    $r->InvoiceDate = \Carbon\Carbon::parse($r->InvoiceDate)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // keep original string
                }
            }

            if (! isset($r->TotalValues) || $r->TotalValues === null) {
                $r->TotalValues = (float) ($r->Amount ?? 0)
                    - (float) ($r->DiscountAmount ?? 0)
                    + (float) ($r->GSTAmount ?? 0)
                    + (float) ($r->ExtraTax ?? 0)
                    + (float) ($r->FurtherTax ?? 0)
                    + (float) ($r->FedPayable ?? 0);
            }

            return $r;
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  SORTING, MULTI-LEVEL GROUPING, SUBTOTALS & GRAND TOTAL ENGINE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Type-aware sorting of report rows by any column (`asc` or `desc`).
     */
    public static function sortReportRows(Collection $rows, string $sortByCol, string $sortDir = 'asc', string $format = 'text'): Collection
    {
        $sortByCol = trim($sortByCol);
        if ($sortByCol === '' || $rows->isEmpty()) {
            return $rows;
        }

        $descending = strtolower(trim($sortDir)) === 'desc';
        $fmt        = strtolower(trim($format));
        $isNumeric  = in_array($fmt, ['integer', 'int', 'qty', 'decimal', 'currency', 'amount', 'number'], true);

        if ($isNumeric) {
            return $rows->sortBy(function ($r) use ($sortByCol) {
                return (float) ($r->{$sortByCol} ?? 0);
            }, SORT_REGULAR, $descending)->values();
        }

        return $rows->sortBy(function ($r) use ($sortByCol) {
            return strtolower(trim((string) ($r->{$sortByCol} ?? '')));
        }, SORT_NATURAL | SORT_FLAG_CASE, $descending)->values();
    }

    /**
     * Compute totals for a collection of rows across all columns where IsTotal = 1.
     */
    public static function calculateTotals(Collection $rows, Collection $columns): array
    {
        $totals = ['__count' => $rows->count()];
        foreach ($columns as $col) {
            $colName = $col->ColumName;
            if ((int) ($col->IsTotal ?? 0) === 1) {
                $totals[$colName] = (float) $rows->sum(function ($r) use ($colName) {
                    return (float) ($r->{$colName} ?? 0);
                });
            }
        }
        return $totals;
    }

    /**
     * Build recursive group tree with subtotals at each level.
     *
     * @param  Collection  $rows
     * @param  Collection  $columns
     * @param  array<int, string>  $groupCols
     * @param  array<string, string>  $colLabelMap
     * @param  int  $level
     * @return array<int, array<string, mixed>>
     */
    public static function buildGroupTree(
        Collection $rows,
        Collection $columns,
        array $groupCols,
        array $colLabelMap,
        int $level = 1
    ): array {
        if (empty($groupCols)) {
            return [];
        }

        $currentCol  = $groupCols[0];
        $remaining   = array_slice($groupCols, 1);
        $columnLabel = $colLabelMap[$currentCol] ?? $currentCol;

        $grouped = $rows->groupBy(function ($r) use ($currentCol) {
            $val = trim((string) ($r->{$currentCol} ?? ''));
            return $val !== '' ? $val : '—';
        });

        $nodes = [];
        foreach ($grouped as $groupKey => $groupRows) {
            $nodes[] = [
                'level'       => $level,
                'column'      => $currentCol,
                'columnLabel' => $columnLabel,
                'value'       => (string) $groupKey,
                'count'       => $groupRows->count(),
                'totals'      => self::calculateTotals($groupRows, $columns),
                'children'    => ! empty($remaining)
                    ? self::buildGroupTree($groupRows, $columns, $remaining, $colLabelMap, $level + 1)
                    : [],
                'rows'        => empty($remaining) ? $groupRows->values() : collect(),
            ];
        }

        return $nodes;
    }

    /**
     * Format a cell or total value according to ColumnFormat ('integer', 'decimal', 'date', 'text').
     */
    public static function formatValue($value, ?string $format = 'text'): string
    {
        $fmt = strtolower(trim((string) $format));
        if ($fmt === 'integer' || $fmt === 'int' || $fmt === 'qty') {
            return number_format((float) ($value ?? 0), 0);
        }
        if ($fmt === 'decimal' || $fmt === 'currency' || $fmt === 'amount' || $fmt === 'number') {
            return number_format((float) ($value ?? 0), 2);
        }
        if ($fmt === 'date') {
            if (empty($value) || $value === '—') {
                return '—';
            }
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }
        $str = trim((string) ($value ?? ''));
        return $str !== '' ? $str : '—';
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  TEMP TABLE VALIDATION & SET-BASED TRANSACTIONS (edit_transaction / reset)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Validate rows in `tbl_temp_reports_detail` for the given RequestID.
     *
     * @return array{status: bool, message: string}|null
     */
    private static function runTempReportValidation(string $requestID, string $tenantDbQuoted): ?array
    {
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_temp_reports_detail` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.CustomReportName), '') = '' THEN 'Report Name cannot be empty'
                        WHEN COALESCE(TRIM(t.CustomLabel), '') = '' THEN CONCAT('Display Name is required for column ', t.ColumName)
                        WHEN t.CustomWidthMin IS NOT NULL AND t.CustomWidthMin < 30 THEN CONCAT('Min Width must be at least 30px for column ', t.ColumName)
                        WHEN t.CustomWidthMax IS NOT NULL AND t.CustomWidthMin IS NOT NULL AND t.CustomWidthMax < t.CustomWidthMin THEN CONCAT('Max Width cannot be less than Min Width for column ', t.ColumName)
                        WHEN NOT EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_reports_detail` d
                            WHERE d.ReportID = t.ReportID AND d.ColumName = t.ColumName
                        ) THEN CONCAT('Invalid report column: ', t.ColumName)
                        WHEN NOT EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_temp_reports_detail` t2
                            WHERE t2.RequestID = t.RequestID AND t2.CustomIsOn = 1
                        ) THEN 'At least one column must be turned ON'
                        ELSE 'Pass'
                    END AS ErrMsg
                FROM {$tenantDbQuoted}.`tbl_temp_reports_detail` t
                WHERE t.RequestID = ?
            ) u ON u.ID = uu.ID
            SET uu.ErrMsg = u.ErrMsg
        ", [$requestID]);

        $errorSummary = DB::connection('landlord')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrMsg ORDER BY t.ErrMsg SEPARATOR ', ') AS ErrMsg
            FROM {$tenantDbQuoted}.`tbl_temp_reports_detail` t
            WHERE t.ErrMsg IS NOT NULL
              AND t.ErrMsg <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status'  => false,
                'message' => $errorSummary->ErrMsg ?: 'Unable to save report settings due to invalid data.',
            ];
        }

        return null;
    }

    /**
     * Validate temp rows in `tbl_temp_reports_detail`, then update `tbl_reports` and `tbl_reports_detail`
     * in the tenant DB by SELECT/JOIN against `RequestID`.
     *
     * @return array{status: bool, message: ?string}
     */
    public static function edit_transaction(string $requestID): array
    {
        $requestID = trim($requestID);
        if ($requestID === '') {
            throw new \RuntimeException('RequestID is required for ReportModel::edit_transaction.');
        }

        $tenantDbQuoted  = self::tenantDatabaseQuoted();
        $validationError = self::runTempReportValidation($requestID, $tenantDbQuoted);
        if ($validationError !== null) {
            return $validationError;
        }

        // 1. Update Report Header (`tbl_reports`) from `tbl_temp_reports_detail` by RequestID
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_reports` r
            INNER JOIN (
                SELECT
                    ReportID,
                    MAX(TRIM(CustomReportName)) AS CustomReportName,
                    MAX(PrintOrientation) AS PrintOrientation,
                    MAX(RowGroupColumnName) AS RowGroupColumnName
                FROM {$tenantDbQuoted}.`tbl_temp_reports_detail`
                WHERE RequestID = ?
                GROUP BY ReportID
            ) t ON r.ReportID = t.ReportID
            SET
                r.CustomName         = t.CustomReportName,
                r.PrintOrientation   = COALESCE(t.PrintOrientation, r.PrintOrientation),
                r.RowGroupColumnName = t.RowGroupColumnName
        ", [$requestID]);

        // 2. Update Report Columns (`tbl_reports_detail`) from `tbl_temp_reports_detail` by RequestID
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_reports_detail` d
            INNER JOIN {$tenantDbQuoted}.`tbl_temp_reports_detail` t
                ON d.ReportID = t.ReportID AND d.ColumName = t.ColumName
            SET
                d.CustomLabel        = TRIM(t.CustomLabel),
                d.CustomDisplayOrder = t.CustomDisplayOrder,
                d.CustomIsOn         = t.CustomIsOn,
                d.IsTotal            = COALESCE(t.IsTotal, d.IsTotal, 0),
                d.CustomWidthMin     = COALESCE(t.CustomWidthMin, d.CustomWidthMin, d.OriginalWidthMin),
                d.WidthMin           = COALESCE(t.CustomWidthMin, d.WidthMin, d.OriginalWidthMin),
                d.CustomWidthMax     = COALESCE(t.CustomWidthMax, d.CustomWidthMax, d.OriginalWidthMax),
                d.WidthMax           = COALESCE(t.CustomWidthMax, d.WidthMax, d.OriginalWidthMax),
                d.CustomAlignment    = COALESCE(t.CustomAlignment, d.CustomAlignment, d.OriginalAlignment),
                d.Alignment          = COALESCE(t.CustomAlignment, d.Alignment, d.OriginalAlignment),
                d.UpdatedBy          = t.UpdatedBy,
                d.UpdatedOn          = t.UpdatedOn
            WHERE t.RequestID = ?
        ", [$requestID]);

        // 3. Also sync CustomName to tbl_module.Name if user renamed the report?
        // Keep tbl_module.Name intact for role permission mapping, while r.CustomName drives display.

        // Clean up temp rows for this RequestID
        DB::connection('landlord')->statement("
            DELETE FROM {$tenantDbQuoted}.`tbl_temp_reports_detail` WHERE RequestID = ?
        ", [$requestID]);

        return [
            'status'  => true,
            'message' => 'Report settings updated successfully.',
        ];
    }

    /**
     * Reset tenant's `tbl_reports` and `tbl_reports_detail` for `$reportId`
     * by updating directly from Master `core_digital_invoicing` tables.
     *
     * @return array{status: bool, message: string}
     */
    public static function reset_transaction(int $reportId, int $userId = 1): array
    {
        $tenantDbQuoted = self::tenantDatabaseQuoted();

        // Ensure any new master columns exist in tenant table first
        DB::connection('landlord')->statement("
            INSERT IGNORE INTO {$tenantDbQuoted}.`tbl_reports`
            SELECT * FROM `core_digital_invoicing`.`tbl_reports` WHERE `ReportID` = ?
        ", [$reportId]);

        DB::connection('landlord')->statement("
            INSERT IGNORE INTO {$tenantDbQuoted}.`tbl_reports_detail`
            SELECT * FROM `core_digital_invoicing`.`tbl_reports_detail` WHERE `ReportID` = ?
        ", [$reportId]);

        // Update tbl_reports from master core_digital_invoicing.tbl_reports
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_reports` t
            INNER JOIN `core_digital_invoicing`.`tbl_reports` m
                ON t.ReportID = m.ReportID
            SET
                t.ReportName         = m.ReportName,
                t.CustomName         = m.ReportName,
                t.ReportType         = m.ReportType,
                t.RowGroupColumnName = m.RowGroupColumnName,
                t.OrderColumnID      = m.OrderColumnID,
                t.PrintOrientation   = m.PrintOrientation,
                t.ReportFunction     = m.ReportFunction,
                t.ReportModel        = m.ReportModel
            WHERE t.ReportID = ?
        ", [$reportId]);

        // Update tbl_reports_detail from master core_digital_invoicing.tbl_reports_detail
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_reports_detail` t
            INNER JOIN `core_digital_invoicing`.`tbl_reports_detail` m
                ON t.ReportID = m.ReportID AND t.ColumName = m.ColumName
            SET
                t.OriginalDisplayOrder = m.OriginalDisplayOrder,
                t.CustomDisplayOrder   = m.OriginalDisplayOrder,
                t.OrignalLabel         = m.OrignalLabel,
                t.CustomLabel          = m.OrignalLabel,
                t.OrignalIsOn          = m.OrignalIsOn,
                t.CustomIsOn           = m.OrignalIsOn,
                t.IsTotal              = m.IsTotal,
                t.IsRunningTotal       = m.IsRunningTotal,
                t.IsVisible            = m.IsVisible,
                t.OriginalWidthMin     = m.OriginalWidthMin,
                t.CustomWidthMin       = m.OriginalWidthMin,
                t.OriginalWidthMax     = m.OriginalWidthMax,
                t.CustomWidthMax       = m.OriginalWidthMax,
                t.OriginalAlignment    = m.OriginalAlignment,
                t.CustomAlignment      = m.OriginalAlignment,
                t.OriginalColumnFormat = m.OriginalColumnFormat,
                t.CustomColumnFormat   = m.OriginalColumnFormat,
                t.WidthMin             = m.WidthMin,
                t.WidthMax             = m.WidthMax,
                t.Alignment            = m.OriginalAlignment,
                t.ColumnFormat         = m.OriginalColumnFormat,
                t.UpdatedOn            = NOW(),
                t.UpdatedBy            = ?
            WHERE t.ReportID = ?
        ", [$userId, $reportId]);

        return [
            'status'  => true,
            'message' => 'Report settings have been reset to original defaults.',
        ];
    }
}

