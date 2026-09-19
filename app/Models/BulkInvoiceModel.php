<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkInvoiceModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_bulk_invoice_request';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'RequestID',
        'Title',
        'SourceType',
        'SourceReference',
        'TotalInvoices',
        'TotalItems',
        'TotalAmount',
        'TotalTax',
        'FbrPostedCount',
        'Status',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn',
    ];

    /**
     * Get properly quoted tenant database name from session
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
     * Listing query for Bulk Invoicing Requests
     */
    public static function getListingData(array $filters = [])
    {
        $query = DB::table('tbl_bulk_invoice_request as b')
            ->select(
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
                'b.CreatedBy',
                'b.CreatedOn'
            )
            ->where('b.Status', '!=', 2)
            ->orderBy('b.ID', 'desc');

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('b.Title', 'LIKE', "%{$s}%")
                  ->orWhere('b.SourceReference', 'LIKE', "%{$s}%")
                  ->orWhere('b.RequestID', 'LIKE', "%{$s}%");
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('b.CreatedOn', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('b.CreatedOn', '<=', $filters['end_date']);
        }

        if (!empty($filters['source_type'])) {
            $query->where('b.SourceType', $filters['source_type']);
        }

        return $query;
    }

    /**
     * Save bulk imported invoices from tbl_temp_invoice_detail into live tables
     *
     * @return array{status: bool, message: ?string, errors?: list<string>, request_id?: string}
     */
    public static function save_bulk_import_transaction(string $requestID, string $title, ?string $filename = null, int $userId = 1): array
    {
        $tenantDbQuoted = self::tenantDatabaseQuoted();

        // 1. Strict Resolution Query: Resolve CustomerID, ProductID, and FBR lookups
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
            LEFT JOIN {$tenantDbQuoted}.`tbl_customer` c 
              ON (
                  LOWER(TRIM(c.`AccountNo`)) = LOWER(TRIM(t.`CustomerCode`))
                  OR LOWER(TRIM(c.`NTN`)) = LOWER(TRIM(t.`CustomerCode`))
                  OR LOWER(TRIM(c.`CNIC`)) = LOWER(TRIM(t.`CustomerCode`))
                  OR LOWER(TRIM(c.`BusinessName`)) = LOWER(TRIM(t.`CustomerCode`))
                  OR LOWER(TRIM(c.`DisplayName`)) = LOWER(TRIM(t.`CustomerCode`))
              ) AND (c.`Status` = 1)
            LEFT JOIN {$tenantDbQuoted}.`tbl_product` p 
              ON (
                  LOWER(TRIM(p.`Code`)) = LOWER(TRIM(t.`ProductCode`))
                  OR LOWER(TRIM(p.`Name`)) = LOWER(TRIM(t.`ProductCode`))
              ) AND (p.`Status` = 1)
            LEFT JOIN `core_digital_invoicing`.`fbr_hscode_uom` fhu 
              ON (
                  (
                      fhu.`hs_CODE` = TRIM(t.`HsCode`)
                      OR (TRIM(t.`HsCode`) LIKE '%.%' AND fhu.`hs_CODE` = CONCAT(SUBSTRING_INDEX(TRIM(t.`HsCode`), '.', 1), '.', RPAD(SUBSTRING_INDEX(TRIM(t.`HsCode`), '.', -1), 4, '0')))
                  )
                  AND (fhu.`description` = TRIM(t.`Uom_Desc`) OR fhu.`uoM_ID` = t.`UomID`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_trans_type_code` fttc 
              ON (
                  TRIM(fttc.`transactioN_DESC`) = TRIM(t.`SaleTypeDesc`) 
                  OR fttc.`transactioN_TYPE_ID` = t.`SaleTypeID`
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr 
              ON (
                  fsttr.`transactioN_TYPE_ID` = COALESCE(fttc.`transactioN_TYPE_ID`, t.`SaleTypeID`)
                  AND (TRIM(fsttr.`ratE_DESC`) = TRIM(t.`SaleTypeToRateDesc`) OR fsttr.`ratE_ID` = t.`SaleTypeToRateID`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss 
              ON (
                  fss.`ratE_ID` = COALESCE(fsttr.`ratE_ID`, t.`SaleTypeToRateID`)
                  AND (TRIM(fss.`srO_DESC`) = TRIM(t.`SroScheduleDesc`) OR fss.`srO_ID` = t.`SroScheduleID`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi 
              ON (
                  fsi.`srO_ID` = COALESCE(fss.`srO_ID`, t.`SroScheduleID`)
                  AND (TRIM(fsi.`srO_ITEM_DESC`) = TRIM(t.`SroItemSerialNo`) OR fsi.`srO_ITEM_ID` = t.`SroItemID`)
              )
            SET 
                t.`CustomerID`         = c.`ID`,
                t.`ProductID`          = p.`ID`,
                t.`ProductDescription` = COALESCE(NULLIF(TRIM(t.`ProductDescription`), ''), p.`SaleInformation`, p.`Name`),
                t.`UomID`              = fhu.`uoM_ID`,
                t.`Uom_Desc`           = COALESCE(fhu.`description`, t.`Uom_Desc`),
                t.`SaleTypeID`         = fttc.`transactioN_TYPE_ID`,
                t.`SaleTypeDesc`       = COALESCE(fttc.`transactioN_DESC`, t.`SaleTypeDesc`),
                t.`SaleTypeToRateID`   = fsttr.`ratE_ID`,
                t.`SaleTypeToRateDesc` = COALESCE(fsttr.`ratE_DESC`, t.`SaleTypeToRateDesc`),
                t.`SroScheduleID`      = fss.`srO_ID`,
                t.`SroScheduleDesc`    = COALESCE(fss.`srO_DESC`, t.`SroScheduleDesc`),
                t.`SroItemID`          = fsi.`srO_ITEM_ID`,
                t.`SroItemSerialNo`    = COALESCE(fsi.`srO_ITEM_DESC`, t.`SroItemSerialNo`),
                t.`Amount`             = IFNULL(t.`ValueSalesExcludingST`, 0.00),
                t.`Rate`               = IF(t.`Quantity` > 0, (IFNULL(t.`ValueSalesExcludingST`, 0.00) / t.`Quantity`), 0.00),
                t.`TPRate`             = IF(t.`Quantity` > 0 AND IFNULL(t.`FixedNotifiedValueOrRetailPrice`, 0) > 0, 
                                        t.`FixedNotifiedValueOrRetailPrice` / t.`Quantity`, COALESCE(p.`TPRate`, 0.00)),
                t.`FixedNotifiedValueOrRetailPrice` = COALESCE(NULLIF(t.`FixedNotifiedValueOrRetailPrice`, 0), 
                                                               IFNULL(p.`TPRate`, 0.00) * t.`Quantity`, 0.00),
                t.`TotalValues`        = IFNULL(t.`ValueSalesExcludingST`, 0.00) + IFNULL(t.`SalesTaxApplicable`, 0.00)
            WHERE t.`RequestID` = ?
        ", [$requestID]);

        // 2. Strict Validation Query
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_temp_invoice_detail` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        -- Basic required invoice fields
                        WHEN COALESCE(TRIM(t.`DocNo`), '') = '' THEN 'Invalid Invoice / Ref No'
                        WHEN COALESCE(TRIM(t.`InvoiceDate`), '') = '' THEN 'Invalid Invoice Date'
                        WHEN (
                            SELECT COUNT(DISTINCT DATE(t2.`InvoiceDate`)) 
                            FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t2 
                            WHERE t2.RequestID = t.RequestID AND t2.DocNo = t.DocNo
                        ) > 1 THEN CONCAT('Different Invoice Dates for same Invoice / Ref No \'', TRIM(t.DocNo), '\'')
                        WHEN (
                            SELECT COUNT(DISTINCT t2.`CustomerCode`) 
                            FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t2 
                            WHERE t2.RequestID = t.RequestID AND t2.DocNo = t.DocNo
                        ) > 1 THEN CONCAT('Different Customers for same Invoice / Ref No \'', TRIM(t.DocNo), '\'')
                        WHEN (
                            SELECT COUNT(DISTINCT t2.`SaleOfTerm`) 
                            FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t2 
                            WHERE t2.RequestID = t.RequestID AND t2.DocNo = t.DocNo
                        ) > 1 THEN CONCAT('Different Sale Of Term for same Invoice / Ref No \'', TRIM(t.DocNo), '\'')
                        WHEN (
                            SELECT COUNT(DISTINCT t2.`InvoiceType`) 
                            FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t2 
                            WHERE t2.RequestID = t.RequestID AND t2.DocNo = t.DocNo
                        ) > 1 THEN CONCAT('Different Invoice Types for same Invoice / Ref No \'', TRIM(t.DocNo), '\'')
                        WHEN EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_sales_invoice_header` h
                            WHERE LOWER(TRIM(h.`DocNo`)) = LOWER(TRIM(t.`DocNo`)) 
                              AND (h.`Status` <> 2 OR h.`Status` IS NULL)
                        ) THEN CONCAT('Invoice / Ref No \'', TRIM(t.DocNo), '\' already exists')

                        -- Customer Validations
                        WHEN COALESCE(TRIM(t.`CustomerCode`), '') = '' THEN 'Missing Customer'
                        WHEN t.`CustomerID` IS NULL THEN CONCAT('Invalid / Unregistered Customer \'', TRIM(t.CustomerCode), '\'')

                        -- Product Validations
                        WHEN COALESCE(TRIM(t.`ProductCode`), '') = '' THEN 'Missing Product'
                        WHEN t.`ProductID` IS NULL THEN CONCAT('Invalid / Unregistered Product \'', TRIM(t.ProductCode), '\'')

                        -- HS Code & UOM Validations
                        WHEN COALESCE(TRIM(t.`HsCode`), '') <> '' 
                          AND NOT EXISTS (
                              SELECT 1 FROM `core_digital_invoicing`.`fbr_hscode_uom` h 
                              WHERE h.`hs_CODE` = TRIM(t.`HsCode`)
                                 OR (TRIM(t.`HsCode`) LIKE '%.%' AND h.`hs_CODE` = CONCAT(SUBSTRING_INDEX(TRIM(t.`HsCode`), '.', 1), '.', RPAD(SUBSTRING_INDEX(TRIM(t.`HsCode`), '.', -1), 4, '0')))
                          ) THEN CONCAT('Invalid HS Code \'', TRIM(t.HsCode), '\'')

                        WHEN COALESCE(TRIM(t.`Uom_Desc`), '') <> '' AND t.`UomID` IS NULL 
                            THEN CONCAT('Invalid UOM \'', TRIM(t.Uom_Desc), '\' for HS Code \'', COALESCE(TRIM(t.HsCode), 'N/A'), '\'')

                        -- FBR Tax Validations
                        WHEN COALESCE(TRIM(t.`SaleTypeDesc`), '') <> '' AND t.`SaleTypeID` IS NULL 
                            THEN CONCAT('Invalid Sale Type \'', TRIM(t.SaleTypeDesc), '\'')
                        WHEN COALESCE(TRIM(t.`SaleTypeToRateDesc`), '') <> '' AND t.`SaleTypeToRateID` IS NULL 
                            THEN CONCAT('Invalid Sale Type Rate \'', TRIM(t.SaleTypeToRateDesc), '\'')
                        WHEN COALESCE(TRIM(t.`SroScheduleDesc`), '') <> '' AND t.`SroScheduleID` IS NULL 
                            THEN CONCAT('Invalid SRO Schedule \'', TRIM(t.SroScheduleDesc), '\'')
                        WHEN COALESCE(TRIM(t.`SroItemSerialNo`), '') <> '' AND t.`SroItemID` IS NULL 
                            THEN CONCAT('Invalid SRO Item \'', TRIM(t.SroItemSerialNo), '\'')

                        -- Numerical Validations
                        WHEN t.`Quantity` <= 0 THEN 'Quantity must be greater than 0'
                        WHEN t.`ValueSalesExcludingST` < 0 THEN 'Value of Sales cannot be negative'
                        WHEN t.`SalesTaxApplicable` < 0 THEN 'Sales Tax cannot be negative'
                        WHEN p.IsFixedRate = 1 AND (t.`SalesTaxApplicable` != (t.`FixedNotifiedValueOrRetailPrice` * (fsttr.ratE_VALUE / 100))) THEN 
				                'Please Check FixedNotifiedValueOrRetailPrice and SalesTaxApplicable'
			            WHEN p.IsFixedRate = 0 AND (t.`SalesTaxApplicable` != (t.`ValueSalesExcludingST` * (fsttr.ratE_VALUE / 100))) THEN 
				                'Please Check ValueSalesExcludingST and SalesTaxApplicable'
                        ELSE 'Pass'
                    END AS ErrMsg
                FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
                LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr ON (fsttr.ratE_ID = t.SaleTypeToRateID AND fsttr.`transactioN_TYPE_ID` = t.`SaleTypeID`)
                LEFT JOIN {$tenantDbQuoted}.tbl_product p ON (p.Code = t.ProductCode)
                LEFT JOIN {$tenantDbQuoted}.tbl_customer c ON (c.AccountNo = t.CustomerCode)
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrMsg = u.ErrMsg;
        ", [$requestID]);

        // 3. Check for any validation errors
        $failedRows = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestID)
            ->where('ErrMsg', '<>', 'Pass')
            ->orderBy('ExcelRow', 'asc')
            ->orderBy('ID', 'asc')
            ->get();

        if ($failedRows->isNotEmpty()) {
            $errors = [];
            foreach ($failedRows as $row) {
                $rowPrefix = !empty($row->ExcelRow) ? "Row {$row->ExcelRow}:" : "Row ID {$row->ID}:";
                $errors[] = "{$rowPrefix} {$row->ErrMsg}";
            }

            return [
                'status' => false,
                'message' => implode("<br>", $errors),
                'errors' => $errors,
            ];
        }

        // 4. Insert Invoice Headers using MySQL Auto-Increment and link InvoiceID
        $distinctInvoices = DB::connection('tenant')->table('tbl_temp_invoice_detail as t')
            ->join('tbl_customer as c', 'c.ID', '=', 't.CustomerID')
            ->where('t.RequestID', $requestID)
            ->where(function ($q) {
                $q->where('t.ErrMsg', 'Pass')->orWhereNull('t.ErrMsg');
            })
            ->select(
                't.DocNo',
                't.InvoiceType',
                't.InvoiceDate',
                't.DocNo2',
                't.DocNo3',
                't.CustomerID',
                't.SaleOfTerm',
                't.InvoiceRefNo',
                DB::raw('COALESCE(c.NTN, c.CNIC) as BuyerNTNCNIC'),
                DB::raw('COALESCE(c.BusinessName, c.DisplayName) as BuyerBusinessName'),
                'c.County as BuyerProvince',
                'c.Address as BuyerAddress',
                'c.IsFiler as BuyerRegistrationType',
                DB::raw('SUM(t.TotalValues) as TotalValues'),
                DB::raw('SUM(t.ValueSalesExcludingST) as ValueSalesExcludingST'),
                DB::raw('SUM(IFNULL(t.FixedNotifiedValueOrRetailPrice, 0.00)) as FixedNotifiedValueOrRetailPrice'),
                DB::raw('SUM(t.SalesTaxApplicable) as SalesTaxApplicable'),
                DB::raw('SUM(IFNULL(t.SalesTaxWithheldAtSource, 0.00)) as SalesTaxWithheldAtSource'),
                DB::raw('SUM(IFNULL(t.ExtraTax, 0.00)) as ExtraTax'),
                DB::raw('SUM(IFNULL(t.FurtherTax, 0.00)) as FurtherTax'),
                DB::raw('SUM(IFNULL(t.FedPayable, 0.00)) as FedPayable'),
                DB::raw('SUM(IFNULL(t.Discount, 0.00)) as Discount')
            )
            ->groupBy('t.DocNo', 't.CustomerID', 't.InvoiceDate', 't.InvoiceType', 't.SaleOfTerm', 't.InvoiceRefNo', 't.DocNo2', 't.DocNo3', 'c.NTN', 'c.CNIC', 'c.BusinessName', 'c.DisplayName', 'c.County', 'c.Address', 'c.IsFiler')
            ->get();

        foreach ($distinctInvoices as $inv) {
            $headerId = DB::connection('tenant')->table('tbl_sales_invoice_header')->insertGetId([
                'InvoiceNumber'                   => 1,
                'InvoiceType'                     => (int) ($inv->InvoiceType ?: 4),
                'InvoiceDate'                     => $inv->InvoiceDate,
                'DocNo'                           => $inv->DocNo,
                'DocNo2'                          => $inv->DocNo2,
                'DocNo3'                          => $inv->DocNo3,
                'CustomerID'                      => $inv->CustomerID,
                'SaleOfTerm'                      => $inv->SaleOfTerm,
                'BuyerNTNCNIC'                    => $inv->BuyerNTNCNIC,
                'BuyerBusinessName'               => $inv->BuyerBusinessName,
                'BuyerProvince'                   => $inv->BuyerProvince,
                'BuyerAddress'                    => $inv->BuyerAddress,
                'InvoiceRefNo'                    => $inv->InvoiceRefNo,
                'BuyerRegistrationType'           => $inv->BuyerRegistrationType,
                'ScenarioId'                      => '',
                'TotalValues'                     => $inv->TotalValues,
                'ValueSalesExcludingST'           => $inv->ValueSalesExcludingST,
                'FixedNotifiedValueOrRetailPrice' => $inv->FixedNotifiedValueOrRetailPrice,
                'SalesTaxApplicable'              => $inv->SalesTaxApplicable,
                'SalesTaxWithheldAtSource'        => $inv->SalesTaxWithheldAtSource,
                'ExtraTax'                        => $inv->ExtraTax,
                'FurtherTax'                      => $inv->FurtherTax,
                'FedPayable'                      => $inv->FedPayable,
                'Discount'                        => $inv->Discount,
                'Statusi'                         => 5,
                'Status'                          => 1,
                'CreatedBy'                       => $userId,
                'CreatedOn'                       => now(),
                'RequestID'                       => $requestID,
            ]);

            // Sync InvoiceID to HeaderID on the header record itself
            DB::connection('tenant')->table('tbl_sales_invoice_header')
                ->where('ID', $headerId)
                ->update(['InvoiceID' => $headerId]);

            // Update temp details for this DocNo with the actual generated header ID
            DB::connection('tenant')->table('tbl_temp_invoice_detail')
                ->where('RequestID', $requestID)
                ->where('DocNo', $inv->DocNo)
                ->update(['InvoiceID' => $headerId]);
        }

        // 5. INSERT into tbl_sales_invoice_detail from temp table
        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.`tbl_sales_invoice_detail` (
                InvoiceID, InvoiceDate, CustomerID, ProductID, Quantity, Rate, Amount, Unit, FBRQty, Weight, GSTAmount,
                HsCode, ProductDescription, UomID, Uom_Desc, SaleTypeID, SaleTypeDesc,
                SaleTypeToRateID, SaleTypeToRateDesc, SroScheduleID, SroScheduleDesc, SroItemID, SroItemSerialNo,
                TotalValues, ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice,TPRate,
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
            FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
            LEFT JOIN {$tenantDbQuoted}.`tbl_product` p ON (p.ID = t.ProductID)
            WHERE t.RequestID = ? AND (t.ErrMsg = 'Pass' OR t.ErrMsg IS NULL)
        ", [$requestID, $requestID]);

        // 7. Calculate Batch Totals & Create Parent Record in tbl_bulk_invoice_request
        $batchSummary = DB::connection('tenant')->table('tbl_sales_invoice_header')
            ->where('RequestID', $requestID)
            ->selectRaw('COUNT(*) as total_invoices, COALESCE(SUM(TotalValues), 0) as total_amount, COALESCE(SUM(SalesTaxApplicable), 0) as total_tax')
            ->first();

        $totalItems = (int) DB::connection('tenant')->table('tbl_sales_invoice_detail')
            ->where('RequestID', $requestID)
            ->count();

        DB::connection('tenant')->table('tbl_bulk_invoice_request')->insert([
            'RequestID'       => $requestID,
            'Title'           => $title ?: 'Bulk Invoice Import',
            'SourceType'      => 'EXCEL_IMPORT',
            'SourceReference' => $filename ?: 'Invoice_Import.xlsx',
            'TotalInvoices'   => (int) ($batchSummary->total_invoices ?? 0),
            'TotalItems'      => $totalItems,
            'TotalAmount'     => (float) ($batchSummary->total_amount ?? 0),
            'TotalTax'        => (float) ($batchSummary->total_tax ?? 0),
            'FbrPostedCount'  => 0,
            'Status'          => 1,
            'CreatedBy'       => $userId,
            'CreatedOn'       => now(),
        ]);

        // 8. Audit Logging
        try {
            \App\Services\AuditLogService::log('CREATE', 'BULK_INVOICES', 0, null, [
                'RequestID'     => $requestID,
                'Title'         => $title,
                'TotalInvoices' => (int) ($batchSummary->total_invoices ?? 0),
                'TotalAmount'   => (float) ($batchSummary->total_amount ?? 0),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log bulk invoice import: ' . $e->getMessage());
        }

        return [
            'status'     => true,
            'message'    => 'Bulk invoices imported successfully.',
            'request_id' => $requestID,
        ];
    }

    /**
     * Rollback / Hard-Delete an entire bulk invoice batch using the history-first pipeline.
     * 
     * Pipeline Steps:
     *   1. FBR safety guard: block if any invoice in batch has been submitted to FBR
     *   2. Move batch request metadata → tbl_bulk_invoice_request_history
     *   3. Move invoice headers → tbl_sales_invoice_header_history
     *   4. Move invoice line items → tbl_sales_invoice_detail_history
     *   5. Hard DELETE line items from tbl_sales_invoice_detail
     *   6. Hard DELETE headers from tbl_sales_invoice_header
     *   7. Hard DELETE batch from tbl_bulk_invoice_request
     *   8. Write audit log
     */
    public static function rollback_batch(string $requestID, int $userId = 1): array
    {
        $now = now()->toDateTimeString();
        $action = 'ROLLBACK';

        // 1. Check if any invoice in this batch has already been posted to FBR
        $postedCount = DB::connection('tenant')->table('tbl_sales_invoice_header')
            ->where('RequestID', $requestID)
            ->whereNotNull('FbrTransactionID')
            ->whereRaw("TRIM(FbrTransactionID) <> ''")
            ->count();

        if ($postedCount > 0) {
            return [
                'status'  => false,
                'message' => "Cannot rollback batch: {$postedCount} invoice(s) have already been submitted to FBR.",
            ];
        }

        // Fetch batch info for audit / note
        $batch = DB::connection('tenant')->table('tbl_bulk_invoice_request')
            ->where('RequestID', $requestID)
            ->first();

        $batchTitle = $batch->Title ?? 'Bulk Invoice Batch';
        $actionNote = "Rollback Batch: " . $batchTitle;

        // 2. Execute history archiving and hard deletion atomically
        try {
            DB::connection('tenant')->transaction(function () use ($requestID, $userId, $now, $action, $actionNote, $batch) {
                // STEP A: Snapshot batch request → tbl_bulk_invoice_request_history
                DB::connection('tenant')->statement("
                    INSERT INTO tbl_bulk_invoice_request_history (
                        OriginalID, RequestID, Action, ActionBy, ActionOn, ActionNote,
                        Title, SourceType, SourceReference, TotalInvoices, TotalItems,
                        TotalAmount, TotalTax, FbrPostedCount, Status, CreatedBy, CreatedOn,
                        UpdatedBy, UpdatedOn
                    )
                    SELECT
                        b.ID, b.RequestID, ?, ?, ?, ?,
                        b.Title, b.SourceType, b.SourceReference, b.TotalInvoices, b.TotalItems,
                        b.TotalAmount, b.TotalTax, b.FbrPostedCount, b.Status, b.CreatedBy, b.CreatedOn,
                        ?, ?
                    FROM tbl_bulk_invoice_request b
                    WHERE b.RequestID = ?
                ", [$action, $userId, $now, $actionNote, $userId, $now, $requestID]);

                // STEP B: Snapshot invoice headers → tbl_sales_invoice_header_history
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
                        Field1, Field2, Field3, Field4,
                        BulkBatchID, Arrears, IsShippingBelowGST,
                        Template, ToLocationID, Tax_ScheduleID,
                        RetailMargin, TOSale, VehicleRegNo,
                        RequestID,
                        Action, ActionBy, ActionOn, ActionNote
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
                        h.Field1, h.Field2, h.Field3, h.Field4,
                        h.BulkBatchID, h.Arrears, h.IsShippingBelowGST,
                        h.Template, h.ToLocationID, h.Tax_ScheduleID,
                        h.RetailMargin, h.TOSale, h.VehicleRegNo,
                        h.RequestID,
                        ?, ?, ?, ?
                    FROM tbl_sales_invoice_header h
                    WHERE h.RequestID = ?
                ", [$action, $userId, $now, $actionNote, $requestID]);

                // STEP C: Snapshot invoice detail rows → tbl_sales_invoice_detail_history
                DB::connection('tenant')->statement("
                    INSERT INTO tbl_sales_invoice_detail_history (
                        OriginalID,
                        InvoiceID, InvoiceDate, CustomerID, ProductID,
                        Quantity, Rate, Amount, FBRQty, Unit, Weight,
                        HsCode, ProductDescription,
                        UomID, Uom_Desc,
                        SaleTypeID, SaleTypeDesc,
                        SaleTypeToRateID, SaleTypeToRateDesc,
                        SroScheduleID, SroScheduleDesc,
                        SroItemID, SroItemSerialNo,
                        TotalValues, ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice,
                        SalesTaxApplicable, SalesTaxWithheldAtSource,
                        ExtraTax, FurtherTax, FedPayable, Discount,
                        CreatedBy, CreatedOn, UpdatedBy, UpdatedOn,
                        GSTAmount, Status,
                        RequestID,
                        Action, ActionBy, ActionOn, ActionNote
                    )
                    SELECT
                        d.ID,
                        d.InvoiceID, d.InvoiceDate, d.CustomerID, d.ProductID,
                        d.Quantity, d.Rate, d.Amount, d.FBRQty, d.Unit, IFNULL(d.Weight, 0.00),
                        d.HsCode, d.ProductDescription,
                        d.UomID, d.Uom_Desc,
                        d.SaleTypeID, d.SaleTypeDesc,
                        d.SaleTypeToRateID, d.SaleTypeToRateDesc,
                        d.SroScheduleID, d.SroScheduleDesc,
                        d.SroItemID, d.SroItemSerialNo,
                        d.TotalValues, d.ValueSalesExcludingST, d.FixedNotifiedValueOrRetailPrice,
                        d.SalesTaxApplicable, d.SalesTaxWithheldAtSource,
                        d.ExtraTax, d.FurtherTax, d.FedPayable, d.Discount,
                        d.CreatedBy, d.CreatedOn, d.UpdatedBy, d.UpdatedOn,
                        d.GSTAmount, d.Status,
                        d.RequestID,
                        ?, ?, ?, ?
                    FROM tbl_sales_invoice_detail d
                    INNER JOIN tbl_sales_invoice_header h ON d.InvoiceID = h.ID
                    WHERE h.RequestID = ?
                ", [$action, $userId, $now, $actionNote, $requestID]);

                // STEP D: Hard delete invoice details
                DB::connection('tenant')->statement("
                    DELETE d FROM tbl_sales_invoice_detail d
                    INNER JOIN tbl_sales_invoice_header h ON d.InvoiceID = h.ID
                    WHERE h.RequestID = ?
                ", [$requestID]);

                // STEP E: Hard delete invoice headers
                DB::connection('tenant')->table('tbl_sales_invoice_header')
                    ->where('RequestID', $requestID)
                    ->delete();

                // STEP F: Hard delete batch request
                DB::connection('tenant')->table('tbl_bulk_invoice_request')
                    ->where('RequestID', $requestID)
                    ->delete();

                // STEP G: Audit log
                try {
                    \App\Services\AuditLogService::log('DELETE', 'BULK_INVOICES', $batch->ID ?? 0, (array) $batch, [
                        'RequestID'     => $requestID,
                        'Action'        => 'Rollback / Hard Deleted Batch',
                        'TotalInvoices' => $batch->TotalInvoices ?? 0,
                        'TotalAmount'   => $batch->TotalAmount ?? 0,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to log bulk invoice rollback: ' . $e->getMessage());
                }
            });

            return [
                'status'  => true,
                'message' => 'Batch and all associated invoices were safely archived to history and permanently removed from active tables.',
            ];
        } catch (\Throwable $e) {
            Log::error('BulkInvoiceModel::rollback_batch exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'status'  => false,
                'message' => 'Rollback failed: ' . $e->getMessage(),
            ];
        }
    }
}

