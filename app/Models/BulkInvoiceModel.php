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
                  fttc.`transactioN_DESC` = TRIM(t.`SaleTypeDesc`) 
                  OR fttc.`transactioN_TYPE_ID` = t.`SaleTypeID`
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr 
              ON (
                  fsttr.`transactioN_TYPE_ID` = COALESCE(fttc.`transactioN_TYPE_ID`, t.`SaleTypeID`)
                  AND (fsttr.`ratE_DESC` = TRIM(t.`SaleTypeToRateDesc`) OR fsttr.`ratE_ID` = t.`SaleTypeToRateID`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss 
              ON (
                  fss.`ratE_ID` = COALESCE(fsttr.`ratE_ID`, t.`SaleTypeToRateID`)
                  AND (fss.`srO_DESC` = TRIM(t.`SroScheduleDesc`) OR fss.`srO_ID` = t.`SroScheduleID`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi 
              ON (
                  fsi.`srO_ID` = COALESCE(fss.`srO_ID`, t.`SroScheduleID`)
                  AND (fsi.`srO_ITEM_DESC` = TRIM(t.`SroItemSerialNo`) OR fsi.`srO_ITEM_ID` = t.`SroItemID`)
              )
            SET 
                t.`CustomerID`       = c.`ID`,
                t.`ProductID`        = p.`ID`,
                t.`ProductDescription` = COALESCE(NULLIF(TRIM(t.`ProductDescription`), ''), p.`SaleInformation`, p.`Name`),
                t.`UomID`            = fhu.`uoM_ID`,
                t.`Uom_Desc`         = fhu.`description`,
                t.`SaleTypeID`       = fttc.`transactioN_TYPE_ID`,
                t.`SaleTypeDesc`     = fttc.`transactioN_DESC`,
                t.`SaleTypeToRateID` = fsttr.`ratE_ID`,
                t.`SaleTypeToRateDesc` = fsttr.`ratE_DESC`,
                t.`SroScheduleID`    = fss.`srO_ID`,
                t.`SroScheduleDesc`  = fss.`srO_DESC`,
                t.`SroItemID`        = fsi.`srO_ITEM_ID`,
                t.`SroItemSerialNo`  = fsi.`srO_ITEM_DESC`,
                t.`TotalValues`      = IFNULL(t.`ValueSalesExcludingST`, 0.00) + IFNULL(t.`SalesTaxApplicable`, 0.00)
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
                        WHEN COALESCE(t.`Quantity`, 0) <= 0 THEN 'Quantity must be greater than 0'
                        WHEN COALESCE(t.`ValueSalesExcludingST`, 0) < 0 THEN 'Value Sales Excluding ST cannot be negative'
                        
                        -- Customer & Product Validations
                        WHEN COALESCE(TRIM(t.`CustomerCode`), '') = '' THEN 'Missing Customer'
                        WHEN t.`CustomerID` IS NULL THEN CONCAT('Invalid / Unregistered Customer \'', TRIM(t.CustomerCode), '\'')
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

                        -- Existing Invoice Check in live tbl_sales_invoice_header
                        WHEN EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_sales_invoice_header` h
                            WHERE BINARY h.`DocNo` = BINARY t.`DocNo`
                              AND IFNULL(h.`Status`, 0) <> 2
                        ) THEN CONCAT('Invoice No \'', TRIM(t.DocNo), '\' already exists in system')

                        ELSE 'Pass'
                    END AS ErrMsg
                FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrMsg = u.ErrMsg
        ", [$requestID]);

        // 3. Collect validation errors ordered by ExcelRow
        $failedRows = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestID)
            ->whereNotNull('ErrMsg')
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
                'InvoiceType'                     => $inv->InvoiceType,
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
                TotalValues, ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice,
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
     * Rollback / Delete an entire bulk invoice batch
     */
    public static function rollback_batch(string $requestID, int $userId = 1): array
    {
        // 1. Check if any invoice in this batch has already been posted to FBR
        $postedCount = DB::connection('tenant')->table('tbl_sales_invoice_header')
            ->where('RequestID', $requestID)
            ->whereNotNull('FbrTransactionID')
            ->where('FbrTransactionID', '<>', '')
            ->count();

        if ($postedCount > 0) {
            return [
                'status'  => false,
                'message' => "Cannot rollback batch: {$postedCount} invoice(s) have already been submitted to FBR.",
            ];
        }

        // 2. Soft-delete / Remove associated records atomically
        DB::connection('tenant')->transaction(function () use ($requestID) {
            // Delete invoice details
            DB::connection('tenant')->table('tbl_sales_invoice_detail')
                ->where('RequestID', $requestID)
                ->delete();

            // Delete invoice headers
            DB::connection('tenant')->table('tbl_sales_invoice_header')
                ->where('RequestID', $requestID)
                ->delete();

            // Mark batch request as deleted (Status = 2)
            DB::connection('tenant')->table('tbl_bulk_invoice_request')
                ->where('RequestID', $requestID)
                ->update([
                    'Status'    => 2,
                    'UpdatedOn' => now(),
                ]);
        });

        // 3. Audit log
        try {
            \App\Services\AuditLogService::log('DELETE', 'BULK_INVOICES', 0, null, [
                'RequestID' => $requestID,
                'Action'    => 'Rollback / Deleted Batch',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log bulk invoice rollback: ' . $e->getMessage());
        }

        return [
            'status'  => true,
            'message' => 'Batch and all associated invoices were successfully removed.',
        ];
    }
}
