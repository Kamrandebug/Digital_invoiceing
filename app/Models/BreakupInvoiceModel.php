<?php

namespace App\Models;

use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BreakupInvoiceModel extends Model
{
    protected $connection = 'tenant';
    protected $table = 'tbl_bulk_invoice_request';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    /**
     * Resolves and validates the active tenant database name wrapped in backticks.
     */
    public static function tenantDatabaseQuoted(): string
    {
        $tenantDb = session('selected_tenant.dbname') ?: config('database.connections.tenant.database');
        $tenantDb = trim((string) $tenantDb);

        if ($tenantDb === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
            throw new \RuntimeException("Invalid or missing tenant database: '{$tenantDb}'.");
        }

        return "`{$tenantDb}`";
    }

    /**
     * Ensure Breakup Invoices is registered in tbl_module for the current tenant.
     */
    public static function ensureModuleRegistered(): void
    {
        try {
            $exists = DB::connection('tenant')->table('tbl_module')
                ->where('CurrentPage', 'breakup-invoices')
                ->orWhere('Name', 'Breakup Invoices')
                ->exists();

            if (! $exists) {
                $refModule = DB::connection('tenant')->table('tbl_module')
                    ->whereIn('CurrentPage', ['bulk-invoices', 'invoices'])
                    ->orderByDesc('DisplayOrder')
                    ->first();

                $mainModuleId = $refModule->MainModuleID ?? 2;
                $displayOrder = ($refModule->DisplayOrder ?? 9) + 1;

                DB::connection('tenant')->table('tbl_module')->insert([
                    'Name'            => 'Breakup Invoices',
                    'Description'     => 'Breakup / Split Invoice Generator',
                    'CreatedOn'       => now(),
                    'CreatedBy'       => auth()->id() ?? 1,
                    'Status'          => 1,
                    'Level'           => 1,
                    'CurrentPage'     => 'breakup-invoices',
                    'MainModuleID'    => $mainModuleId,
                    'ModuleURL'       => 'breakup-invoices',
                    'MainModuleIcon'  => 'ri-split-cells-horizontal',
                    'IsReport'        => 0,
                    'IsGlobalSetting' => 0,
                    'DisplayOrder'    => $displayOrder,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('BreakupInvoiceModel::ensureModuleRegistered error: ' . $e->getMessage());
        }
    }

    /**
     * Query builder for Breakup Invoices listing.
     */
    public static function getListingData(array $filters = [])
    {
        $sessionUserName = auth()->user()?->name ?: (session('selected_tenant.user_name') ?: 'System');
        $quotedFallback  = DB::connection('tenant')->getPdo()->quote($sessionUserName);

        $query = DB::connection('tenant')->table('tbl_bulk_invoice_request as b')
            ->leftJoin('tbl_user as u', function ($join) {
                $join->on('u.ID', '=', 'b.CreatedBy')
                     ->orOn('u.MasterUserID', '=', 'b.CreatedBy');
            })
            ->where('b.SourceType', 'BREAKUP_GENERATOR')
            ->where('b.Status', '!=', 2)
            ->select([
                'b.ID',
                'b.RequestID',
                'b.Title',
                'b.SourceType',
                'b.SourceReference',
                'b.TotalInvoices',
                'b.TotalItems',
                'b.TotalAmount',
                'b.TotalTax',
                'b.FbrPostedCount',
                'b.Status',
                'b.CreatedOn',
                DB::raw("(SELECT COUNT(*) FROM tbl_sales_invoice_header h WHERE h.RequestID = b.RequestID AND (h.Statusi = 7 OR (h.FbrTransactionID IS NOT NULL AND TRIM(h.FbrTransactionID) <> ''))) as LivePostedCount"),
                DB::raw("(SELECT COUNT(*) FROM tbl_sales_invoice_header h WHERE h.RequestID = b.RequestID AND (h.Statusi = 6 OR (h.Statusi = 0 AND (h.FbrTransactionID IS NULL OR TRIM(h.FbrTransactionID) = '') AND h.FbrResponseJson IS NOT NULL AND TRIM(h.FbrResponseJson) <> ''))) as LiveErrorCount"),
                DB::raw("(SELECT COUNT(*) FROM tbl_sales_invoice_header h WHERE h.RequestID = b.RequestID) as LiveTotalCount"),
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.FirstName, ''), ' ', COALESCE(u.LastName, ''))), ''), u.Email, {$quotedFallback}) as CreatedByName"),
            ])
            ->orderByDesc('b.ID');

        if (! empty($filters['search'])) {
            $s = trim((string) $filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('b.Title', 'LIKE', "%{$s}%")
                    ->orWhere('b.RequestID', 'LIKE', "%{$s}%")
                    ->orWhere('b.SourceReference', 'LIKE', "%{$s}%");
            });
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('b.CreatedOn', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('b.CreatedOn', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * Fetch complete product defaults from tbl_product for auto-filling the generator form.
     */
    public static function getProductBreakupInfo(int $productId): ?object
    {
        return DB::connection('tenant')->table('tbl_product')
            ->where('ID', $productId)
            ->where('Status', 1)
            ->select([
                'ID',
                'Code as ProductCode',
                'Code',
                'Name',
                'HsCode',
                'SalePrice',
                'Cost',
                'TPRate',
                'IsFixedRate',
                'IsFixedRate as IsFixedValue',
                'Unit',
                'UomID',
                'Uom_Desc',
                'SaleTypeID',
                'SaleTypeDesc',
                'SaleTypeToRateID',
                'SaleTypeToRateDesc',
                'SroScheduleID',
                'SroScheduleDesc',
                'SroItemID',
                'SroItemSerialNo',
            ])
            ->first();
    }

    /**
     * Staging Generation: Takes target quantity and constraints, generates randomized rows
     * with smoothing, and stores them in tbl_temp_invoice_detail with the given RequestID.
     */
    public static function generate_breakup_temp(string $requestID, array $params, int $userId): array
    {
        $customerId   = (int) ($params['customer_id'] ?? 0);
        $productId    = (int) ($params['product_id'] ?? 0);
        $targetQty    = (float) ($params['total_target_qty'] ?? 0);
        $minQty       = (float) ($params['min_qty'] ?? 1);
        $maxQty       = (float) ($params['max_qty'] ?? 1);
        $fromDate     = trim((string) ($params['from_date'] ?? date('Y-m-d')));
        $toDate       = trim((string) ($params['to_date'] ?? date('Y-m-d')));
        $numberingMode = trim((string) ($params['numbering_mode'] ?? 'auto'));
        $prefix        = trim((string) ($params['invoice_prefix'] ?? ''));
        $saleOfTerm    = trim((string) ($params['sale_of_term'] ?? 'CASH')) ?: 'CASH';
        $allowDecimal = ! empty($params['allow_decimal_qty']);
        $doc2         = trim((string) ($params['doc2'] ?? ''));
        $doc3         = trim((string) ($params['doc3'] ?? ''));

        if ($targetQty <= 0) {
            return ['status' => false, 'message' => 'Total target quantity must be greater than 0.'];
        }
        if ($minQty <= 0 || $maxQty < $minQty) {
            return ['status' => false, 'message' => 'Invalid quantity range: Min Qty must be positive and Max Qty >= Min Qty.'];
        }

        // 1. Fetch Customer Info
        $customer = DB::connection('tenant')->table('tbl_customer')
            ->where('ID', $customerId)
            ->where('Status', 1)
            ->first();

        if (! $customer) {
            return ['status' => false, 'message' => 'Selected customer was not found or is inactive.'];
        }

        // 2. Fetch Product Info
        $product = self::getProductBreakupInfo($productId);
        if (! $product) {
            return ['status' => false, 'message' => 'Selected product was not found or is inactive.'];
        }

        // Rates & Taxes (User override if supplied; otherwise product default)
        $rate = isset($params['sale_rate']) && is_numeric($params['sale_rate']) && (float) $params['sale_rate'] > 0
            ? (float) $params['sale_rate']
            : (float) ($product->SalePrice ?? 0);

        if ($rate <= 0) {
            return ['status' => false, 'message' => 'Sale rate must be greater than 0.'];
        }

        $isFixedValue = isset($params['is_fixed_value']) ? (int) $params['is_fixed_value'] : (int) ($product->IsFixedRate ?? $product->IsFixedValue ?? 0);
        $tpRate = isset($params['tp_rate']) && is_numeric($params['tp_rate'])
            ? (float) $params['tp_rate']
            : (float) ($product->TPRate ?? 0);

        // Tax Rate percentage extraction (e.g. "18%" -> 0.18, "Goods at standard rate (18%)" -> 0.18)
        $taxRateDesc = (string) ($params['sale_type_to_rate_desc'] ?? $product->SaleTypeToRateDesc ?? '');
        $taxPercent = 0.00;
        if (preg_match('/(\d+(\.\d+)?)%/', $taxRateDesc, $m)) {
            $taxPercent = ((float) $m[1]) / 100.0;
        }

        // Date calculations
        try {
            $carbonFrom = Carbon::parse($fromDate)->startOfDay();
            $carbonTo   = Carbon::parse($toDate)->startOfDay();
            if ($carbonTo->lt($carbonFrom)) {
                $carbonTo = $carbonFrom->copy();
            }
        } catch (\Throwable) {
            $carbonFrom = now()->startOfDay();
            $carbonTo   = now()->startOfDay();
        }
        $dayDiff = max(0, $carbonFrom->diffInDays($carbonTo));

        // 3. Clear any existing temp rows for this RequestID
        DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestID)
            ->delete();

        // 4. Target Distribution Algorithm with Smart Smoothing
        $remainingQty = $targetQty;
        $qtyList = [];
        $maxLoops = 50000;
        $loops = 0;

        while ($remainingQty > 0.00001 && $loops < $maxLoops) {
            $loops++;

            if ($remainingQty <= $maxQty + 0.00001) {
                // If remainder is <= maxQty, finalize directly
                $qtyList[] = $allowDecimal ? round($remainingQty, 4) : round($remainingQty);
                $remainingQty = 0;
                break;
            }

            // Pick random qty within [minQty, maxQty]
            if ($allowDecimal) {
                $pick = $minQty + (mt_rand() / mt_getrandmax()) * ($maxQty - $minQty);
                $pick = round($pick, 4);
                $pick = max($minQty, min($maxQty, $pick));
            } else {
                $pick = (float) mt_rand((int) $minQty, (int) $maxQty);
            }

            // Smoothing check: avoid leaving an orphaned remainder less than minQty
            $leftover = $remainingQty - $pick;
            if ($leftover > 0 && $leftover < $minQty) {
                // Adjust pick so remaining is either 0 or at least minQty
                if ($remainingQty <= $maxQty * 1.5) {
                    $pick = round($remainingQty / 2, $allowDecimal ? 4 : 0);
                } else {
                    $pick = $remainingQty - $minQty;
                }
            }

            $pick = max($minQty, min($remainingQty, $pick));
            $qtyList[] = $allowDecimal ? round($pick, 4) : round($pick);
            $remainingQty -= $pick;
        }

        // Ensure sum equals target exactly
        $currentSum = array_sum($qtyList);
        $diff = round($targetQty - $currentSum, 4);
        if (abs($diff) > 0.0001 && ! empty($qtyList)) {
            $lastKey = count($qtyList) - 1;
            $qtyList[$lastKey] = round($qtyList[$lastKey] + $diff, $allowDecimal ? 4 : 0);
        }

        $totalInvoices = count($qtyList);
        $now = now()->toDateTimeString();
        $tempRows = [];

        // Build individual rows with realistic advancing dates and full FBR fields
        foreach ($qtyList as $index => $qty) {
            $seq = $index + 1;
            if ($numberingMode === 'custom' && $prefix !== '') {
                $docNo = $prefix . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            } else {
                $docNo = 'DRAFT-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            }

            // Distributed date
            $dayOffset = $totalInvoices > 1 ? (int) round(($index / ($totalInvoices - 1)) * $dayDiff) : 0;
            $invoiceDate = $carbonFrom->copy()->addDays($dayOffset)->format('Y-m-d');

            $valueExclST = round($qty * $rate, 2);

            // Fixed Notified Value: total line retail value (Quantity * TPRate) when IsFixedRate == 1
            $fixedNotifiedVal = ($isFixedValue === 1 && $tpRate > 0) ? round($qty * $tpRate, 2) : 0.00;

            // Taxable basis: TPRate when IsFixedRate == 1, otherwise valueExclST
            if ($isFixedValue === 1 && $tpRate > 0) {
                $taxBase = $fixedNotifiedVal;
                $salesTax = round($taxBase * $taxPercent, 2);
            } else {
                $salesTax = round($valueExclST * $taxPercent, 2);
            }

            $totalValue = round($valueExclST + $salesTax, 2);

            $tempRows[] = [
                'ExcelRow'                        => $seq,
                'InvoiceType'                     => 4, // Sale Invoice
                'InvoiceDate'                     => $invoiceDate,
                'DocNo'                           => $docNo,
                'DocNo2'                          => $doc2,
                'DocNo3'                          => $doc3,
                'InvoiceRefNo'                    => $docNo,
                'SaleOfTerm'                      => $saleOfTerm,
                'CustomerID'                      => $customerId,
                'CustomerCode'                    => !empty($customer->AccountNo) ? $customer->AccountNo : (!empty($customer->NTN) ? $customer->NTN : ('CUST-' . $customerId)),
                'ProductID'                       => $productId,
                'ProductCode'                     => $product->ProductCode ?? null,
                'HsCode'                          => $product->HsCode ?? null,
                'ProductDescription'              => $product->Name ?? null,
                'SaleTypeID'                      => $product->SaleTypeID ?? null,
                'SaleTypeDesc'                    => $params['sale_type_desc'] ?? $product->SaleTypeDesc ?? null,
                'SaleTypeToRateID'                => $product->SaleTypeToRateID ?? null,
                'SaleTypeToRateDesc'              => $taxRateDesc,
                'Quantity'                        => $qty,
                'UomID'                           => $product->UomID ?? null,
                'Uom_Desc'                        => $product->Uom_Desc ?? null,
                'ValueSalesExcludingST'           => $valueExclST,
                'FixedNotifiedValueOrRetailPrice' => $fixedNotifiedVal,
                'TPRate'                          => $tpRate,
                'SalesTaxApplicable'              => $salesTax,
                'ExtraTax'                        => 0.00,
                'FurtherTax'                      => 0.00,
                'SalesTaxWithheldAtSource'        => 0.00,
                'FedPayable'                      => 0.00,
                'Discount'                        => 0.00,
                'SroScheduleID'                   => $product->SroScheduleID ?? null,
                'SroScheduleDesc'                 => $product->SroScheduleDesc ?? null,
                'SroItemID'                       => $product->SroItemID ?? null,
                'SroItemSerialNo'                 => $product->SroItemSerialNo ?? null,
                'PetroleumLevyOn'                 => 'No Levy',
                'TotalValues'                     => $totalValue,
                'Statusi'                         => 5, // Draft Staging
                'ErrMsg'                          => 'Pass',
                'CreatedBy'                       => $userId,
                'CreatedOn'                       => $now,
                'RequestID'                       => $requestID,
            ];
        }

        // Chunked insert into tbl_temp_invoice_detail
        foreach (array_chunk($tempRows, 100) as $chunk) {
            DB::connection('tenant')->table('tbl_temp_invoice_detail')->insert($chunk);
        }

        // Return summary
        $summary = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestID)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(Quantity), 0) as total_qty, COALESCE(SUM(ValueSalesExcludingST), 0) as total_excl_tax, COALESCE(SUM(SalesTaxApplicable), 0) as total_tax, COALESCE(SUM(TotalValues), 0) as total_amount, COALESCE(SUM(FixedNotifiedValueOrRetailPrice), 0) as total_fixed_notified')
            ->first();

        return [
            'status'               => true,
            'message'              => "Successfully generated {$totalInvoices} breakup invoices.",
            'total_invoices'       => (int) ($summary->total_count ?? 0),
            'total_qty'            => (float) ($summary->total_qty ?? 0),
            'total_excl_tax'       => (float) ($summary->total_excl_tax ?? 0),
            'total_tax'            => (float) ($summary->total_tax ?? 0),
            'total_amount'         => (float) ($summary->total_amount ?? 0),
            'total_fixed_notified' => (float) ($summary->total_fixed_notified ?? 0),
        ];
    }

    /**
     * Inline row edit in preview table: Recalculates line value & tax and updates tbl_temp_invoice_detail.
     */
    public static function edit_temp_row(int $tempId, float $newQty, ?string $newDate, ?float $newRate, int $userId): array
    {
        $row = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('ID', $tempId)
            ->first();

        if (! $row) {
            return ['status' => false, 'message' => 'Row not found in staging table.'];
        }

        if ($newQty <= 0) {
            return ['status' => false, 'message' => 'Quantity must be greater than 0.'];
        }

        $rate = $newRate !== null && $newRate > 0
            ? $newRate
            : ($row->Quantity > 0 ? (float) ($row->ValueSalesExcludingST / $row->Quantity) : 0.00);

        // Tax rate parsing from row
        $taxPercent = 0.00;
        if (preg_match('/(\d+(\.\d+)?)%/', (string) $row->SaleTypeToRateDesc, $m)) {
            $taxPercent = ((float) $m[1]) / 100.0;
        }

        $valueExclST = round($newQty * $rate, 2);
        $tpRate = (float) ($row->TPRate ?? 0);
        $isFixed = ((float) ($row->FixedNotifiedValueOrRetailPrice ?? 0) > 0 || (int) ($row->IsFixedRate ?? 0) === 1) && $tpRate > 0 ? 1 : 0;
        $fixedNotifiedVal = $isFixed === 1 ? round($newQty * $tpRate, 2) : 0.00;

        if ($isFixed === 1) {
            $salesTax = round($fixedNotifiedVal * $taxPercent, 2);
        } else {
            $salesTax = round($valueExclST * $taxPercent, 2);
        }

        $totalValue = round($valueExclST + $salesTax, 2);

        $updateData = [
            'Quantity'                        => $newQty,
            'ValueSalesExcludingST'           => $valueExclST,
            'FixedNotifiedValueOrRetailPrice' => $fixedNotifiedVal,
            'SalesTaxApplicable'              => $salesTax,
            'TotalValues'                     => $totalValue,
            'CreatedBy'                       => $userId,
        ];

        if (! empty($newDate)) {
            $updateData['InvoiceDate'] = $newDate;
        }

        DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('ID', $tempId)
            ->update($updateData);

        // Batch totals
        $summary = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $row->RequestID)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(Quantity), 0) as total_qty, COALESCE(SUM(ValueSalesExcludingST), 0) as total_excl_tax, COALESCE(SUM(SalesTaxApplicable), 0) as total_tax, COALESCE(SUM(TotalValues), 0) as total_amount, COALESCE(SUM(FixedNotifiedValueOrRetailPrice), 0) as total_fixed_notified')
            ->first();

        return [
            'status'         => true,
            'row'            => [
                'id'             => $tempId,
                'qty'            => $newQty,
                'rate'           => $rate,
                'value_excl_st'  => $valueExclST,
                'fixed_notified' => $fixedNotifiedVal,
                'tax'            => $salesTax,
                'total'          => $totalValue,
                'date'           => $newDate ?: $row->InvoiceDate,
            ],
            'batch_totals'   => [
                'total_invoices'       => (int) ($summary->total_count ?? 0),
                'total_qty'            => (float) ($summary->total_qty ?? 0),
                'total_excl_tax'       => (float) ($summary->total_excl_tax ?? 0),
                'total_tax'            => (float) ($summary->total_tax ?? 0),
                'total_amount'         => (float) ($summary->total_amount ?? 0),
                'total_fixed_notified' => (float) ($summary->total_fixed_notified ?? 0),
            ],
        ];
    }

    /**
     * Delete an individual row from the preview table.
     */
    public static function delete_temp_row(int $tempId): array
    {
        $row = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('ID', $tempId)
            ->first();

        if (! $row) {
            return ['status' => false, 'message' => 'Row not found.'];
        }

        $requestId = $row->RequestID;

        DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('ID', $tempId)
            ->delete();

        $summary = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestId)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(Quantity), 0) as total_qty, COALESCE(SUM(ValueSalesExcludingST), 0) as total_excl_tax, COALESCE(SUM(SalesTaxApplicable), 0) as total_tax, COALESCE(SUM(TotalValues), 0) as total_amount')
            ->first();

        return [
            'status'       => true,
            'batch_totals' => [
                'total_invoices' => (int) ($summary->total_count ?? 0),
                'total_qty'      => (float) ($summary->total_qty ?? 0),
                'total_excl_tax' => (float) ($summary->total_excl_tax ?? 0),
                'total_tax'      => (float) ($summary->total_tax ?? 0),
                'total_amount'   => (float) ($summary->total_amount ?? 0),
            ],
        ];
    }

    /**
     * SQL-level validation for staged rows prior to saving.
     */
    public static function runTempBreakupValidation(string $requestID, string $tenantDbQuoted): ?array
    {
        // 1. Check if rows exist
        $count = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestID)
            ->count();

        if ($count === 0) {
            return ['status' => false, 'message' => 'No staged invoice records found to commit.'];
        }

        // 2. Validate customer existence and status
        $invalidCust = DB::connection('tenant')->selectOne("
            SELECT t.CustomerID, t.DocNo
            FROM tbl_temp_invoice_detail t
            LEFT JOIN tbl_customer c ON (c.ID = t.CustomerID AND c.Status = 1)
            WHERE t.RequestID = ? AND c.ID IS NULL
            LIMIT 1
        ", [$requestID]);

        if ($invalidCust) {
            return ['status' => false, 'message' => "Validation failed: Customer on invoice {$invalidCust->DocNo} is inactive or invalid."];
        }

        // 3. Validate product existence and status
        $invalidProd = DB::connection('tenant')->selectOne("
            SELECT t.ProductID, t.DocNo
            FROM tbl_temp_invoice_detail t
            LEFT JOIN tbl_product p ON (p.ID = t.ProductID AND p.Status = 1)
            WHERE t.RequestID = ? AND p.ID IS NULL
            LIMIT 1
        ", [$requestID]);

        if ($invalidProd) {
            return ['status' => false, 'message' => "Validation failed: Product on invoice {$invalidProd->DocNo} is inactive or invalid."];
        }

        // 4. Validate numeric integrity (Quantity > 0, Rate > 0, TotalValues > 0)
        $invalidNumbers = DB::connection('tenant')->selectOne("
            SELECT t.DocNo, t.Quantity, t.TotalValues
            FROM tbl_temp_invoice_detail t
            WHERE t.RequestID = ? AND (t.Quantity <= 0 OR t.TotalValues <= 0)
            LIMIT 1
        ", [$requestID]);

        if ($invalidNumbers) {
            return ['status' => false, 'message' => "Validation failed: Invoice {$invalidNumbers->DocNo} has invalid quantity ({$invalidNumbers->Quantity}) or total ({$invalidNumbers->TotalValues})."];
        }

        // Mark all validated temp rows as Pass
        DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestID)
            ->update(['ErrMsg' => 'Pass']);

        return null;
    }

    /**
     * Commits staged breakup rows into live sales invoice tables via pure raw SQL.
     */
    public static function save_transaction(string $requestID, string $title, int $userId): array
    {
        $requestID = trim($requestID);
        if ($requestID === '') {
            return ['status' => false, 'message' => 'RequestID is required.'];
        }

        $tenantDbQuoted = self::tenantDatabaseQuoted();

        // 1. Run Pre-save SQL Validation
        $validationError = self::runTempBreakupValidation($requestID, $tenantDbQuoted);
        if ($validationError !== null) {
            return $validationError;
        }

        $now = now()->toDateTimeString();

        try {
            return DB::connection('tenant')->transaction(function () use ($requestID, $title, $userId, $now) {
                // 2. Lock-free sequence extraction using UNION of live invoices and staged invoices
                $draftDocs = DB::connection('tenant')->table('tbl_temp_invoice_detail')
                    ->where('RequestID', $requestID)
                    ->where('ErrMsg', 'Pass')
                    ->where('DocNo', 'LIKE', 'DRAFT-%')
                    ->select('DocNo')
                    ->distinct()
                    ->orderBy('ID', 'asc')
                    ->pluck('DocNo');

                if ($draftDocs->isNotEmpty()) {
                    // Extract max numeric portion from tbl_sales_invoice_header UNION ALL tbl_temp_invoice_detail without table locks
                    $maxRow = DB::connection('tenant')->selectOne("
                        SELECT MAX(num) AS max_num FROM (
                            SELECT CAST(SUBSTRING(DocNo, 5) AS UNSIGNED) AS num 
                            FROM tbl_sales_invoice_header 
                            WHERE DocNo LIKE 'INV-%'
                            UNION ALL
                            SELECT CAST(SUBSTRING(DocNo, 5) AS UNSIGNED) AS num 
                            FROM tbl_temp_invoice_detail 
                            WHERE RequestID = ? AND DocNo LIKE 'INV-%'
                        ) AS combined
                    ", [$requestID]);

                    $nextSeq = ($maxRow && (int) $maxRow->max_num > 0)
                        ? ((int) $maxRow->max_num + 1)
                        : 1;

                    foreach ($draftDocs as $idx => $draftDoc) {
                        $realDocNo = 'INV-' . str_pad((string) ($nextSeq + $idx), 6, '0', STR_PAD_LEFT);
                        DB::connection('tenant')->table('tbl_temp_invoice_detail')
                            ->where('RequestID', $requestID)
                            ->where('DocNo', $draftDoc)
                            ->update([
                                'DocNo'        => $realDocNo,
                                'InvoiceRefNo' => $realDocNo,
                            ]);
                    }
                }

                // Verify valid rows exist in staging table
                $rowCount = DB::connection('tenant')->table('tbl_temp_invoice_detail')
                    ->where('RequestID', $requestID)
                    ->where('ErrMsg', 'Pass')
                    ->count();

                if ($rowCount === 0) {
                    throw new \RuntimeException('No valid invoices to insert from staging table.');
                }

                $sampleProduct = DB::connection('tenant')->table('tbl_temp_invoice_detail')
                    ->where('RequestID', $requestID)
                    ->whereNotNull('ProductDescription')
                    ->value('ProductDescription') ?? 'Product';

                // 3. Set-Based Bulk INSERT into tbl_sales_invoice_header on tenant connection
                DB::connection('tenant')->statement("
                    INSERT INTO `tbl_sales_invoice_header` (
                        InvoiceNumber, InvoiceType, InvoiceDate, DocNo, DocNo2, DocNo3, CustomerID, SaleOfTerm,
                        BuyerNTNCNIC, BuyerBusinessName, BuyerProvince, BuyerAddress, InvoiceRefNo, BuyerRegistrationType,
                        ScenarioId, TotalValues, ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice,
                        SalesTaxApplicable, SalesTaxWithheldAtSource, ExtraTax, FurtherTax, FedPayable, Discount,
                        Statusi, `Status`, CreatedBy, CreatedOn, RequestID
                    )
                    SELECT 
                        1 AS InvoiceNumber,
                        MAX(t.InvoiceType) AS InvoiceType,
                        MAX(t.InvoiceDate) AS InvoiceDate,
                        t.DocNo,
                        t.DocNo2,
                        t.DocNo3,
                        MAX(t.CustomerID) AS CustomerID,
                        MAX(t.SaleOfTerm) AS SaleOfTerm,
                        MAX(COALESCE(NULLIF(TRIM(c.NTN), ''), NULLIF(TRIM(c.CNIC), ''))) AS BuyerNTNCNIC,
                        MAX(COALESCE(NULLIF(TRIM(c.BusinessName), ''), NULLIF(TRIM(c.DisplayName), ''), CONCAT('Customer #', t.CustomerID))) AS BuyerBusinessName,
                        MAX(COALESCE(NULLIF(TRIM(c.County), ''), '')) AS BuyerProvince,
                        MAX(COALESCE(NULLIF(TRIM(c.Address), ''), '')) AS BuyerAddress,
                        MAX(t.InvoiceRefNo) AS InvoiceRefNo,
                        MAX(COALESCE(c.IsFiler, '')) AS BuyerRegistrationType,
                        '' AS ScenarioId,
                        SUM(t.TotalValues) AS TotalValues,
                        SUM(t.ValueSalesExcludingST) AS ValueSalesExcludingST,
                        SUM(IFNULL(t.FixedNotifiedValueOrRetailPrice, 0.00)) AS FixedNotifiedValueOrRetailPrice,
                        SUM(t.SalesTaxApplicable) AS SalesTaxApplicable,
                        SUM(t.SalesTaxWithheldAtSource) AS SalesTaxWithheldAtSource,
                        SUM(t.ExtraTax) AS ExtraTax,
                        SUM(t.FurtherTax) AS FurtherTax,
                        SUM(t.FedPayable) AS FedPayable,
                        SUM(t.Discount) AS Discount,
                        5 AS Statusi,
                        1 AS `Status`,
                        {$userId} AS CreatedBy,
                        NOW() AS CreatedOn,
                        ? AS RequestID
                    FROM `tbl_temp_invoice_detail` t
                    LEFT JOIN `tbl_customer` c ON (c.ID = t.CustomerID)
                    WHERE t.RequestID = ? AND t.ErrMsg = 'Pass'
                    GROUP BY t.DocNo, t.DocNo2, t.DocNo3
                    ORDER BY MIN(t.ID) ASC
                ", [$requestID, $requestID]);

                // Sync InvoiceID = ID on tbl_sales_invoice_header in one single query on tenant connection
                DB::connection('tenant')->statement("
                    UPDATE `tbl_sales_invoice_header`
                    SET InvoiceID = ID
                    WHERE RequestID = ? AND (InvoiceID IS NULL OR InvoiceID = 0)
                ", [$requestID]);

                // Link temp staging rows to generated Header IDs via (RequestID, DocNo) on tenant connection
                DB::connection('tenant')->statement("
                    UPDATE `tbl_temp_invoice_detail` t
                    JOIN `tbl_sales_invoice_header` h 
                      ON (h.RequestID = t.RequestID AND h.DocNo = t.DocNo)
                    SET t.InvoiceID = h.ID
                    WHERE t.RequestID = ?
                ", [$requestID]);

                // 4. Set-Based Bulk INSERT into tbl_sales_invoice_detail on tenant connection
                DB::connection('tenant')->statement("
                    INSERT INTO `tbl_sales_invoice_detail` (
                        InvoiceID, InvoiceDate, CustomerID, ProductID, Quantity, Rate, Amount, Unit, FBRQty, Weight, GSTAmount,
                        HsCode, ProductDescription, UomID, Uom_Desc, SaleTypeID, SaleTypeDesc,
                        SaleTypeToRateID, SaleTypeToRateDesc, SroScheduleID, SroScheduleDesc, SroItemID, SroItemSerialNo,
                        TotalValues, ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice, TPRate,
                        SalesTaxApplicable, SalesTaxWithheldAtSource, ExtraTax, FurtherTax, FedPayable, Discount,
                        Status, CreatedBy, CreatedOn, RequestID
                    )
                    SELECT 
                        t.InvoiceID,
                        t.InvoiceDate,
                        t.CustomerID,
                        t.ProductID,
                        t.Quantity,
                        IF(t.Quantity > 0, (t.ValueSalesExcludingST / t.Quantity), 0.00) AS Rate,
                        t.ValueSalesExcludingST AS Amount,
                        COALESCE(p.Unit, 1) AS Unit,
                        t.Quantity AS FBRQty,
                        IFNULL(t.Weight, 0.00),
                        t.SalesTaxApplicable AS GSTAmount,
                        t.HsCode,
                        t.ProductDescription,
                        t.UomID,
                        t.Uom_Desc,
                        t.SaleTypeID,
                        t.SaleTypeDesc,
                        t.SaleTypeToRateID,
                        t.SaleTypeToRateDesc,
                        t.SroScheduleID,
                        IFNULL(t.SroScheduleDesc, ''),
                        t.SroItemID,
                        IFNULL(t.SroItemSerialNo, ''),
                        IFNULL(t.TotalValues, 0.00),
                        IFNULL(t.ValueSalesExcludingST, 0.00),
                        IFNULL(t.FixedNotifiedValueOrRetailPrice, 0.00),
                        IFNULL(t.TPRate, 0.00),
                        IFNULL(t.SalesTaxApplicable, 0.00),
                        IFNULL(t.SalesTaxWithheldAtSource, 0.00),
                        IFNULL(t.ExtraTax, 0.00),
                        IFNULL(t.FurtherTax, 0.00),
                        IFNULL(t.FedPayable, 0.00),
                        IFNULL(t.Discount, 0.00),
                        1 AS `Status`,
                        {$userId} AS CreatedBy,
                        NOW() AS CreatedOn,
                        ?
                    FROM `tbl_temp_invoice_detail` t
                    LEFT JOIN `tbl_product` p ON (p.ID = t.ProductID)
                    WHERE t.RequestID = ? AND t.ErrMsg = 'Pass'
                ", [$requestID, $requestID]);

                // 5. Calculate Batch Totals & Create Parent Record in tbl_bulk_invoice_request
                $batchSummary = DB::connection('tenant')->table('tbl_sales_invoice_header')
                    ->where('RequestID', $requestID)
                    ->selectRaw('COUNT(*) as total_invoices, COALESCE(SUM(TotalValues), 0) as total_amount, COALESCE(SUM(SalesTaxApplicable), 0) as total_tax')
                    ->first();

                $totalItems = (int) DB::connection('tenant')->table('tbl_sales_invoice_detail')
                    ->where('RequestID', $requestID)
                    ->count();

                $batchTitle = trim($title) ?: ('Breakup: ' . $sampleProduct);

                DB::connection('tenant')->table('tbl_bulk_invoice_request')->insert([
                    'RequestID'       => $requestID,
                    'Title'           => $batchTitle,
                    'SourceType'      => 'BREAKUP_GENERATOR',
                    'SourceReference' => $sampleProduct,
                    'TotalInvoices'   => (int) ($batchSummary->total_invoices ?? 0),
                    'TotalItems'      => $totalItems,
                    'TotalAmount'     => (float) ($batchSummary->total_amount ?? 0),
                    'TotalTax'        => (float) ($batchSummary->total_tax ?? 0),
                    'FbrPostedCount'  => 0,
                    'Status'          => 1,
                    'CreatedBy'       => $userId,
                    'CreatedOn'       => $now,
                ]);

                // 6. Audit Logging
                try {
                    AuditLogService::log('CREATE', 'BREAKUP_INVOICE', 0, null, [
                        'RequestID'     => $requestID,
                        'Title'         => $batchTitle,
                        'TotalInvoices' => (int) ($batchSummary->total_invoices ?? 0),
                        'TotalAmount'   => (float) ($batchSummary->total_amount ?? 0),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to log breakup invoice creation: ' . $e->getMessage());
                }

                return [
                    'status'     => true,
                    'message'    => "Successfully imported {$batchSummary->total_invoices} breakup invoices.",
                    'request_id' => $requestID,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('BreakupInvoiceModel::save_transaction failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['status' => false, 'message' => 'Save transaction failed: ' . $e->getMessage()];
        }
    }

    /**
     * Pre-flight safety check before delete or rollback:
     * Blocks if any invoice in the batch has already been submitted to FBR.
     */
    public static function validate_can_delete(string $requestID): ?array
    {
        $stats = DB::connection('tenant')->selectOne("
            SELECT
                COUNT(*) AS total_count,
                COALESCE(SUM(CASE WHEN (h.Statusi = 7 OR (h.FbrTransactionID IS NOT NULL AND TRIM(h.FbrTransactionID) <> '')) THEN 1 ELSE 0 END), 0) AS posted_count
            FROM tbl_sales_invoice_header h
            WHERE h.RequestID = ?
        ", [$requestID]);

        $postedCount = (int) ($stats->posted_count ?? 0);
        if ($postedCount > 0) {
            return [
                'status'  => false,
                'message' => "Cannot delete batch: {$postedCount} invoice(s) in this batch have already been submitted to FBR.",
            ];
        }

        return null;
    }

    /**
     * Move header rows to history table via raw SQL INSERT INTO ... SELECT
     */
    private static function move_history_header(string $requestID, int $userId, string $now, string $action): void
    {
        if (Schema::connection('tenant')->hasTable('tbl_sales_invoice_header_history')) {
            DB::connection('tenant')->statement("
                INSERT INTO tbl_sales_invoice_header_history (
                    OriginalID,
                    InvoiceID, InvoiceNumber, InvoiceType, InvoiceDate,
                    DocNo, DocNo2, DocNo3,
                    CustomerID, SaleOfTerm,
                    BuyerNTNCNIC, BuyerBusinessName, BuyerProvince, BuyerAddress,
                    InvoiceRefNo, BuyerRegistrationType, ScenarioId,
                    TotalValues, ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice,
                    SalesTaxApplicable, SalesTaxWithheldAtSource,
                    ExtraTax, FurtherTax, FedPayable, Discount,
                    NetAmount, FbrTransactionID, FbrResponseJson,
                    Statusi, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn,
                    Status, Typei, BuyerAddress1, DueDate, BillNumber, Notes, TermDays,
                    SubTotal, SubTotalBeforeDiscount, InvoiceTaxType,
                    GSTId, GSTAmount, GSTBalance,
                    DiscountAllowed, DiscountAmount, Total, Balance,
                    Filter1, Filter2, Filter3, Filter4,
                    Action, ActionBy, ActionOn, ActionNote, RequestID
                )
                SELECT
                    h.ID,
                    h.InvoiceID, h.InvoiceNumber, h.InvoiceType, h.InvoiceDate,
                    h.DocNo, h.DocNo2, h.DocNo3,
                    h.CustomerID, h.SaleOfTerm,
                    h.BuyerNTNCNIC, h.BuyerBusinessName, h.BuyerProvince, h.BuyerAddress,
                    h.InvoiceRefNo, h.BuyerRegistrationType, h.ScenarioId,
                    h.TotalValues, h.ValueSalesExcludingST, h.FixedNotifiedValueOrRetailPrice,
                    h.SalesTaxApplicable, h.SalesTaxWithheldAtSource,
                    h.ExtraTax, h.FurtherTax, h.FedPayable, h.Discount,
                    h.NetAmount, h.FbrTransactionID, h.FbrResponseJson,
                    h.Statusi, h.CreatedBy, h.CreatedOn, h.UpdatedBy, h.UpdatedOn,
                    h.Status, h.Typei, h.BuyerAddress1, h.DueDate, h.BillNumber, h.Notes, h.TermDays,
                    h.SubTotal, h.SubTotalBeforeDiscount, h.InvoiceTaxType,
                    h.GSTId, h.GSTAmount, h.GSTBalance,
                    h.DiscountAllowed, h.DiscountAmount, h.Total, h.Balance,
                    h.Filter1, h.Filter2, h.Filter3, h.Filter4,
                    ?, ?, ?, 'Breakup Invoice Delete', h.RequestID
                FROM tbl_sales_invoice_header h
                WHERE h.RequestID = ?
            ", [$action, $userId, $now, $requestID]);
        }
    }

    /**
     * Move detail line items to history table via raw SQL INSERT INTO ... SELECT
     */
    private static function move_history_detail(string $requestID, int $userId, string $now, string $action): void
    {
        if (Schema::connection('tenant')->hasTable('tbl_sales_invoice_detail_history')) {
            DB::connection('tenant')->statement("
                INSERT INTO tbl_sales_invoice_detail_history (
                    OriginalID,
                    InvoiceID, InvoiceDate, CustomerID, ProductID,
                    Quantity, Rate, Amount, Unit, FBRQty, Weight, GSTAmount,
                    HsCode, ProductDescription, UomID, Uom_Desc,
                    SaleTypeID, SaleTypeDesc, SaleTypeToRateID, SaleTypeToRateDesc,
                    SroScheduleID, SroScheduleDesc, SroItemID, SroItemSerialNo,
                    TotalValues, ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice, TPRate,
                    SalesTaxApplicable, SalesTaxWithheldAtSource, ExtraTax, FurtherTax, FedPayable, Discount,
                    Status, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn,
                    Action, ActionBy, ActionOn, ActionNote, RequestID
                )
                SELECT
                    d.ID,
                    d.InvoiceID, d.InvoiceDate, d.CustomerID, d.ProductID,
                    d.Quantity, d.Rate, d.Amount, d.Unit, d.FBRQty, d.Weight, d.GSTAmount,
                    d.HsCode, d.ProductDescription, d.UomID, d.Uom_Desc,
                    d.SaleTypeID, d.SaleTypeDesc, d.SaleTypeToRateID, d.SaleTypeToRateDesc,
                    d.SroScheduleID, d.SroScheduleDesc, d.SroItemID, d.SroItemSerialNo,
                    d.TotalValues, d.ValueSalesExcludingST, d.FixedNotifiedValueOrRetailPrice, d.TPRate,
                    d.SalesTaxApplicable, d.SalesTaxWithheldAtSource, d.ExtraTax, d.FurtherTax, d.FedPayable, d.Discount,
                    d.Status, d.CreatedBy, d.CreatedOn, d.UpdatedBy, d.UpdatedOn,
                    ?, ?, ?, 'Breakup Invoice Delete', d.RequestID
                FROM tbl_sales_invoice_detail d
                WHERE d.RequestID = ?
            ", [$action, $userId, $now, $requestID]);
        }
    }

    /**
     * Move batch request record to history table via raw SQL INSERT INTO ... SELECT
     */
    private static function move_history_batch(string $requestID, int $userId, string $now, string $action): void
    {
        if (Schema::connection('tenant')->hasTable('tbl_bulk_invoice_request_history')) {
            DB::connection('tenant')->statement("
                INSERT INTO tbl_bulk_invoice_request_history (
                    OriginalID, RequestID, Action, ActionBy, ActionOn, ActionNote,
                    Title, SourceType, SourceReference, TotalInvoices, TotalItems,
                    TotalAmount, TotalTax, FbrPostedCount, Status, CreatedBy, CreatedOn,
                    UpdatedBy, UpdatedOn
                )
                SELECT
                    b.ID, b.RequestID, ?, ?, ?, 'Breakup Batch Delete',
                    b.Title, b.SourceType, b.SourceReference, b.TotalInvoices, b.TotalItems,
                    b.TotalAmount, b.TotalTax, b.FbrPostedCount, b.Status, b.CreatedBy, b.CreatedOn,
                    ?, ?
                FROM tbl_bulk_invoice_request b
                WHERE b.RequestID = ?
            ", [$action, $userId, $now, $userId, $now, $requestID]);
        }
    }

    /**
     * Delete transaction: Executes the complete history-first hard delete pipeline.
     */
    public static function delete_transaction(string $requestID, int $userId = 1): array
    {
        $now = now()->toDateTimeString();

        // 1. Pre-flight Validation
        $validationError = self::validate_can_delete($requestID);
        if ($validationError !== null) {
            return $validationError;
        }

        $batch = DB::connection('tenant')->table('tbl_bulk_invoice_request')
            ->where('RequestID', $requestID)
            ->first();

        try {
            DB::connection('tenant')->transaction(function () use ($requestID, $userId, $now, $batch) {
                // STEP A: Snapshot Batch Request
                self::move_history_batch($requestID, $userId, $now, 'DELETE');

                // STEP B: Snapshot Headers
                self::move_history_header($requestID, $userId, $now, 'DELETE');

                // STEP C: Snapshot Detail Items
                self::move_history_detail($requestID, $userId, $now, 'DELETE');

                // STEP D: Hard DELETE line items
                DB::connection('tenant')->table('tbl_sales_invoice_detail')
                    ->where('RequestID', $requestID)
                    ->delete();

                // STEP E: Hard DELETE headers
                DB::connection('tenant')->table('tbl_sales_invoice_header')
                    ->where('RequestID', $requestID)
                    ->delete();

                // STEP F: Hard DELETE batch
                DB::connection('tenant')->table('tbl_bulk_invoice_request')
                    ->where('RequestID', $requestID)
                    ->delete();

                // STEP G: Clean staging rows if any remain
                DB::connection('tenant')->table('tbl_temp_invoice_detail')
                    ->where('RequestID', $requestID)
                    ->delete();
            });

            // Audit Log
            try {
                AuditLogService::log('DELETE', 'BREAKUP_INVOICE', $batch->ID ?? 0, (array) $batch, [
                    'RequestID'  => $requestID,
                    'ActionNote' => 'Breakup invoice batch rolled back and archived.',
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to log breakup batch delete: ' . $e->getMessage());
            }

            return ['status' => true, 'message' => 'Batch successfully rolled back and archived.'];
        } catch (\Throwable $e) {
            Log::error('BreakupInvoiceModel::delete_transaction failed: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Rollback failed: ' . $e->getMessage()];
        }
    }
}

