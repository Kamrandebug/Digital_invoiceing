<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceModel extends Model
{
    use HasFactory;

    protected $table      = 'tbl_temp_invoice_detail';
    protected $primaryKey = 'ID';

    public $timestamps = false;

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'InvoiceID',
        'CustomerID',
        'Address',
        'InvoiceDate',
        'TermDays',
        'DueDate',
        'DocNo',
        'ProductID',
        'Details',
        'ProductDescription',
        'HsCode',
        'UomID',
        'SaleTypeID',
        'SaleTypeToRateID',
        'SroScheduleID',
        'SroItemID',
        'Quantity',
        'Rate',
        'Amount',
        'DiscountPercentage',
        'DiscountAmount',
        'GstPercentage',
        'GSTAmount',
        'Total',
        'Notes',
        'DiscountAllowed',
        'Type',
        'Status',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn',
        'ErrMsg',
    ];

    protected $casts = [
        'CreatedOn'          => 'datetime',
        'UpdatedOn'          => 'datetime',
        'Status'             => 'integer',
        'Quantity'           => 'float',
        'Rate'               => 'float',
        'Amount'             => 'float',
        'DiscountAmount'     => 'float',
        'GSTAmount'          => 'float',
        'Total'              => 'float',
        'TermDays'           => 'integer',
    ];

    // ══════════════════════════════════════════════════════════
    //  LOAD HELPERS — for invoice form dropdowns
    // ══════════════════════════════════════════════════════════

    public static function Load_Customer()
    {
        // Raw SQL string used to populate the Customer dropdown
        $query = "SELECT ID, DisplayName, FirstName, LastName, BusinessName, IsFiler, SalesTaxWithheldAtSource, ExtraTax, FurtherTax, FedPayable
                  FROM tbl_customer
                  WHERE Status = 1";

        return $query;
    }

    public static function Load_Product()
    {
        // Product dropdown for invoice: include tax/FBR fields for auto-fill on line item.
        $query = "SELECT p.ID, p.Name AS ProductName, p.SalePrice, 0 AS GstPercentage,
                  p.HsCode, p.SaleInformation, p.UomID,
                  (SELECT u.UnitName FROM tbl_Unit u WHERE u.id = p.UomID LIMIT 1) AS UomName,
                  p.SaleTypeID, p.SaleTypeToRateID, p.SroScheduleID, p.SroItemID, p.TPRate, p.IsFixedRate
                  FROM tbl_product p
                  WHERE p.Status = 1";

        return $query;
    }

    /**
     * Load Invoice Types from core_digital_invoicing.fbr_doc_type_code.
     * Returns docTypeId as value, docDescription as display.
     *
     * Note: Invoice pages run on the tenant DB connection, which often cannot read
     * cross-schema FBR tables. We try landlord/mysql (central reference) first.
     */
    public static function Load_InvoiceTypes()
    {
        $table = 'core_digital_invoicing.fbr_doc_type_code';

        $connectionOrder = array_values(array_unique(array_filter([
            'landlord',
            'mysql',
            config('database.default'),
        ])));

        foreach ($connectionOrder as $connectionName) {
            if (! config("database.connections.{$connectionName}")) {
                continue;
            }
            try {
                $rows = DB::connection($connectionName)
                    ->table($table)
                    ->select('docTypeId', 'docDescription')
                    ->orderBy('docDescription')
                    ->get();
                if ($rows->isNotEmpty()) {
                    return $rows;
                }
            } catch (\Throwable $e) {
                \Log::debug("Load_InvoiceTypes connection={$connectionName}: " . $e->getMessage());
            }
        }

        \Log::warning('Load_InvoiceTypes: no rows from FBR table; using fallback list.');

        return collect([
            (object) ['docTypeId' => 4, 'docDescription' => 'Sale Invoice'],
            (object) ['docTypeId' => 9, 'docDescription' => 'Debit Note'],
        ]);
    }

   // ══════════════════════════════════════════════════════════
    //  LISTING
    // ══════════════════════════════════════════════════════════

    /**
     * Customer column label: BusinessName, else DisplayName, else first + last name (join alias: cust).
     */
    public static function sqlCustomerDisplayName()
    {
        return DB::raw(
            'COALESCE(NULLIF(TRIM(cust.BusinessName),\'\'), NULLIF(TRIM(cust.DisplayName),\'\'), '
            . 'TRIM(CONCAT(COALESCE(cust.FirstName,\'\'),\' \',COALESCE(cust.LastName,\'\')))) AS CustomerDisplayName'
        );
    }

    public static function getlistingdata($filters =[])
    {
        // 1. Point directly to tbl_sales_invoice_header (aliased as 'h')
        $query = DB::table('tbl_sales_invoice_header as h')
            ->leftJoin('tbl_customer as cust', 'h.CustomerID', '=', 'cust.ID')
            ->select(
                'h.ID          as HeaderID', 
                'h.DocNo',
                'h.InvoiceDate',
                'h.DueDate',
                'h.Status',
                'h.CustomerID',
                'h.Notes',
                // Grab totals directly from the header table instead of calculating them
                'h.SubTotal',
                'h.DiscountAmount AS TotalDiscount',
                'h.SalesTaxApplicable      AS TotalGST',
                'h.TotalValues      AS TotalAmount',
                'h.FbrTransactionID',
                'h.FbrResponseJson',
                'h.CreatedOn',
                self::sqlCustomerDisplayName()
            )
            ->where('h.Status', '!=', 2); // Hide Archived Invoices

        // 2. Search Filter
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('h.DocNo', 'LIKE', "%{$s}%")
                  ->orWhere('cust.BusinessName', 'LIKE', "%{$s}%")
                  ->orWhereRaw(
                      'COALESCE(NULLIF(TRIM(cust.DisplayName),\'\'), '
                      . 'TRIM(CONCAT(COALESCE(cust.FirstName,\'\'),\' \',COALESCE(cust.LastName,\'\')))) LIKE ?',
                      ["%{$s}%"]
                  );
            });
        }

        // 3. Date Filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('h.InvoiceDate', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('h.InvoiceDate', '<=', $filters['end_date']);
        }

        // 4. Order by latest created date
        return $query->orderByDesc('h.CreatedOn');
    }

    // ══════════════════════════════════════════════════════════
    //  TRANSACTIONS
    // ══════════════════════════════════════════════════════════

    /**
     * @throws \RuntimeException
     */
    private static function tenantDatabaseQuoted(): string
    {
        $tenantDb = session('selected_tenant.dbname') ?: config('database.connections.tenant.database');
        $tenantDb = trim((string) $tenantDb);

        if ($tenantDb === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
            throw new \RuntimeException('Invalid tenant database name in session.');
        }

        return '`' . str_replace('`', '``', $tenantDb) . '`';
    }

    /**
     * @return array{status: bool, message: string}|null null when validation passes
     */
    private static function runTempInvoiceValidation(string $requestID, string $tenantDbQuoted): ?array
    {
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_temp_invoice_detail` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.DocNo), '') = '' THEN 'Invalid Document Number'
                        WHEN t.CustomerID IS NULL OR t.CustomerID <= 0 THEN 'Invalid Customer'
                        WHEN NOT EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_customer` c
                            WHERE c.ID = t.CustomerID AND c.Status = 1
                        ) THEN 'Invalid Customer'
                        WHEN t.ProductID IS NULL OR t.ProductID <= 0 THEN 'Invalid Product'
                        WHEN NOT EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_product` p
                            WHERE p.ID = t.ProductID AND p.Status = 1
                        ) THEN 'Invalid Product'
                        WHEN EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_sales_invoice_header` h
                            WHERE BINARY h.DocNo = BINARY t.DocNo
                              AND IFNULL(h.Status, 0) <> 2
                              AND h.ID <> t.TID
                        ) THEN 'Document No already exist'
                        ELSE 'Pass'
                    END AS ErrMsg
                FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
                WHERE t.RequestID = ?
            ) u ON u.ID = uu.ID
            SET uu.ErrMsg = u.ErrMsg
        ", [$requestID]);

        $errorSummary = DB::connection('landlord')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrMsg ORDER BY t.ErrMsg SEPARATOR ', ') AS ErrMsg
            FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
            WHERE t.ErrMsg IS NOT NULL
              AND t.ErrMsg <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrMsg ?: 'Unable to save invoice due to invalid data.',
            ];
        }

        return null;
    }

    private static function persistInvoiceFromTemp(string $requestID, string $tenantDbQuoted): void
    {
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.tbl_temp_invoice_detail uu
            JOIN (
                SELECT  t.ID, HsCode,fho.`description` AS Uom_Desc,fttc.`transactioN_DESC` AS SaleTypeDesc,
                        fsttr.`ratE_DESC` AS SaleTypeToRateDesc, fss.`srO_DESC` AS SroScheduleDesc,
                        fsi.`srO_ITEM_DESC` AS SroItemSerialNo,ProductDescription,
                        t.Amount AS `ValueSalesExcludingST`,
                        IFNULL(t.TPRate,0.00) * IFNULL(t.Quantity,0.00) AS `FixedNotifiedValueOrRetailPrice`,
                        t.GSTAmount AS `SalesTaxApplicable`,t.`TotalValues`, t.RequestID
                FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
                LEFT JOIN `core_digital_invoicing`.`fbr_hscode_uom` fho ON (fho.`uoM_ID` = t.UomID AND fho.hs_CODE = t.HsCode)
                LEFT JOIN `core_digital_invoicing`.`fbr_trans_type_code` fttc ON (fttc.`transactioN_TYPE_ID` = t.SaleTypeID)
                LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr ON (fsttr.`ratE_ID` = t.SaleTypeToRateID AND fsttr.`transactioN_TYPE_ID` = fttc.`transactioN_TYPE_ID`)
                LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss ON (fss.`srO_ID` = t.SroScheduleID)
                LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi ON (fsi.`srO_ITEM_ID` = t.SroItemID)
                WHERE t.RequestID = ?
            ) u ON uu.ID = u.ID
            SET
                uu.Uom_Desc           = u.Uom_Desc,
                uu.SaleTypeDesc       = u.SaleTypeDesc,
                uu.SaleTypeToRateDesc = u.SaleTypeToRateDesc,
                uu.SroScheduleDesc    = u.SroScheduleDesc,
                uu.SroItemSerialNo    = u.SroItemSerialNo,
                uu.`ValueSalesExcludingST` = u.`ValueSalesExcludingST`,
                uu.`FixedNotifiedValueOrRetailPrice` = u.`FixedNotifiedValueOrRetailPrice`,
                uu.`SalesTaxApplicable` = u.`SalesTaxApplicable`,
                uu.`TotalValues` = u.`TotalValues`
                ", [$requestID]);

        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.tbl_sales_invoice_header (ID,InvoiceID,InvoiceNumber,InvoiceType,InvoiceDate,DocNo,DocNo2,DocNo3,CustomerID,BuyerNTNCNIC,BuyerBusinessName,BuyerProvince,BuyerAddress,InvoiceRefNo,BuyerRegistrationType,ScenarioId,TotalValues,ValueSalesExcludingST,FixedNotifiedValueOrRetailPrice,SalesTaxApplicable,SalesTaxWithheldAtSource,ExtraTax,FurtherTax,FedPayable,Discount,Statusi,CreatedBy,CreatedOn,UpdatedBy,UpdatedOn)
            SELECT t.TID AS ID,t.InvoiceID,1 AS InvoiceNumber,t.InvoiceType,t.InvoiceDate,t.DocNo,t.DocNo2,t.DocNo3,t.CustomerID,
            c.`NTN` AS BuyerNTNCNIC,c.`BusinessName` AS BuyerBusinessName,c.County AS BuyerProvince, c.`Address` AS BuyerAddress,
            ''AS InvoiceRefNo,c.IsFiler AS BuyerRegistrationType,
            ''AS ScenarioId,SUM(t.TotalValues) AS TotalValues,SUM(t.ValueSalesExcludingST) AS ValueSalesExcludingST,
            SUM(t.FixedNotifiedValueOrRetailPrice) AS FixedNotifiedValueOrRetailPrice,SUM(t.SalesTaxApplicable) AS SalesTaxApplicable,
            t.SalesTaxWithheldAtSource,
            SUM(t.ExtraTax) AS ExtraTax,SUM(t.FurtherTax) AS FurtherTax,SUM(t.FedPayable) AS FedPayable,SUM(t.Discount) AS Discount,
            t.Statusi,t.CreatedBy,t.CreatedOn,t.UpdatedBy,t.UpdatedOn
            FROM {$tenantDbQuoted}.tbl_temp_invoice_detail t
            INNER JOIN {$tenantDbQuoted}.`tbl_customer` c ON (c.ID = t.CustomerID)
            WHERE t.RequestID = ?
            GROUP BY t.RequestID
        ", [$requestID]);

        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.`tbl_sales_invoice_detail` (InvoiceID,InvoiceDate,CustomerID,ProductID,Quantity,Rate,Amount,Unit,Weight,GSTAmount,HsCode,ProductDescription,UomID,Uom_Desc,SaleTypeID,SaleTypeDesc,SaleTypeToRateID,SaleTypeToRateDesc,SroScheduleID,SroScheduleDesc,SroItemID,SroItemSerialNo,TotalValues,ValueSalesExcludingST,FixedNotifiedValueOrRetailPrice,SalesTaxApplicable,SalesTaxWithheldAtSource,ExtraTax,FurtherTax,FedPayable,Discount,Status,CreatedBy,CreatedOn,UpdatedBy,UpdatedOn)
            SELECT 	t.InvoiceID, t.InvoiceDate, t.CustomerID, t.ProductID, t.Quantity, t.Rate, t.Amount, t.Unit, t.Weight,t.GSTAmount,
                t.HsCode, t.ProductDescription AS SaleInformation,t.UomID, t.Uom_Desc AS Uom_Desc,
                t.SaleTypeID, t.SaleTypeDesc AS SaleTypeDesc,
                t.SaleTypeToRateID, t.SaleTypeToRateDesc AS SaleTypeToRateDesc,
                t.SroScheduleID, IFNULL(t.SroScheduleDesc,'') AS SroScheduleDesc,
                t.SroItemID, IFNULL(t.SroItemSerialNo,'') AS SroItemSerialNo,
                IFNULL(t.TotalValues,0.00),
                IFNULL(t.ValueSalesExcludingST,0.00) AS ValueSalesExcludingST,
                -- IFNULL(t.TPRate,0.00) * IFNULL(t.Quantity,0.00) AS FixedNotifiedValueOrRetailPrice,
                IFNULL(t.FixedNotifiedValueOrRetailPrice,0.00) AS FixedNotifiedValueOrRetailPrice,
                IFNULL(t.SalesTaxApplicable,0.00) AS SalesTaxApplicable,
	            IFNULL(t.SalesTaxWithheldAtSource,0.00),IFNULL(t.ExtraTax,0.00), IFNULL(t.FurtherTax,0.00), IFNULL(t.FedPayable,0.00), IFNULL(t.Discount,0.00),
                1 AS `Status`,
                t.CreatedBy, t.CreatedOn, t.UpdatedBy, t.UpdatedOn
            FROM {$tenantDbQuoted}.`tbl_temp_invoice_detail` t
            WHERE t.RequestID = ?
        ", [$requestID]);
    }

    /**
     * Execute save: validate temp rows, then insert header + detail from tbl_temp_invoice_detail for the given RequestID.
     *
     * @return array{status: bool, message: ?string}
     */
    public static function save_transaction($requestID)
    {
        $requestID = trim((string) $requestID);
        if ($requestID === '') {
            throw new \RuntimeException('RequestID is required for save_transaction.');
        }

        $tenantDbQuoted = self::tenantDatabaseQuoted();
        $validationError = self::runTempInvoiceValidation($requestID, $tenantDbQuoted);
        if ($validationError !== null) {
            return $validationError;
        }

        self::persistInvoiceFromTemp($requestID, $tenantDbQuoted);

        return [
            'status' => true,
            'message' => null,
        ];
    }

    /**
     * Validate temp rows, remove existing header/lines for this edit, then persist from temp (same as save path).
     *
     * @return array{status: bool, message: ?string}
     */
    public static function edit_transaction($requestID)
    {
        $requestID = trim((string) $requestID);
        if ($requestID === '') {
            throw new \RuntimeException('RequestID is required for edit_transaction.');
        }

        $tenantDbQuoted = self::tenantDatabaseQuoted();
        $validationError = self::runTempInvoiceValidation($requestID, $tenantDbQuoted);
        if ($validationError !== null) {
            return $validationError;
        }

        DB::connection('landlord')->statement("
            DELETE FROM {$tenantDbQuoted}.tbl_sales_invoice_detail
            WHERE InvoiceID IN (
                SELECT TID FROM {$tenantDbQuoted}.tbl_temp_invoice_detail WHERE RequestID = ?
            )
        ", [$requestID]);

        DB::connection('landlord')->statement("
            DELETE FROM {$tenantDbQuoted}.tbl_sales_invoice_header
            WHERE ID IN (
                SELECT TID FROM {$tenantDbQuoted}.tbl_temp_invoice_detail WHERE RequestID = ?
            )
        ", [$requestID]);

        self::persistInvoiceFromTemp($requestID, $tenantDbQuoted);

        return [
            'status' => true,
            'message' => null,
        ];
    }

    public static function delete_transaction($requestID)
    {
        DB::unprepared("
            UPDATE tbl_sales_invoice_header uu
            JOIN (
                SELECT 2 AS Status, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn, TID
                FROM tbl_temp_invoice_detail
                WHERE RequestID = '$requestID'
            ) u ON uu.ID = u.TID
            SET
                uu.Status = u.Status,
                uu.UpdatedBy = u.UpdatedBy,
                uu.UpdatedOn = u.UpdatedOn
        ");
    }

    /**
     * Build JSON from header + business + customer + lines (SQL), POST to FBR sandbox, save response on header.
     *
     * @return array{success: bool, message: string}
     */
    public static function post_to_fbr(int $headerId): array
    {
        // PHP default max_execution_time (often 60s) can kill the script before Guzzle's timeout throws.
        // Allow the HTTP client enough wall time; Guzzle still enforces connect/request timeouts.
        $fbrTimeout = (float) config('services.fbr.timeout', 120);
        if ($fbrTimeout < 1) {
            $fbrTimeout = 120;
        }
        $phpMaxExec = (int) config('services.fbr.max_execution_time', (int) ceil($fbrTimeout + 90));
        if ($phpMaxExec < 120) {
            $phpMaxExec = 180;
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit($phpMaxExec);
        }

        $header = DB::table('tbl_sales_invoice_header')
            ->where('ID', $headerId)
            ->where('Status', '!=', 2)
            ->first();

        if (! $header) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        if (trim((string) ($header->FbrTransactionID ?? '')) !== '') {
            return ['success' => false, 'message' => 'This invoice was already posted to FBR.'];
        }

        DB::statement('SET SESSION group_concat_max_len = 1048576');

        $sql = <<<'SQL'
SELECT
    CONCAT(
        '{',
        '"invoiceType" : ', JSON_QUOTE(IF(h.InvoiceType = 4, 'Sale Invoice', 'Debit Note')), ',',
        '"invoiceDate" : ', JSON_QUOTE(DATE_FORMAT(h.InvoiceDate, '%Y-%m-%d')), ',',
        '"sellerBusinessName" : ', JSON_QUOTE(IFNULL(b.BusinessName, '')), ',',
        '"sellerProvince" : ', JSON_QUOTE(IFNULL(b.Province, '')), ',',
        '"sellerNTNCNIC" : ', JSON_QUOTE(IFNULL(b.NTN, '')), ',',
        '"sellerAddress" : ', JSON_QUOTE(IFNULL(b.Address, '')), ',',
        '"buyerNTNCNIC" : ', JSON_QUOTE(IFNULL(c.NTN, '')), ',',
        '"buyerBusinessName" : ', JSON_QUOTE(IFNULL(c.BusinessName, '')), ',',
        '"buyerProvince" : ', JSON_QUOTE(IFNULL(c.County, '')), ',',
        '"buyerAddress" : ', JSON_QUOTE(IFNULL(c.Address, '')), ',',
        '"invoiceRefNo" : ', JSON_QUOTE(''), ',',
        -- '"scenarioId" : ', JSON_QUOTE(IF(c.IsFiler = 1, 'SN001', 'SN002')), ',',
        '"buyerRegistrationType" : ', JSON_QUOTE(IF(c.IsFiler = 1, 'Registered', 'Unregistered')), ',',
        '"items":[',
        IFNULL((
            SELECT GROUP_CONCAT(
                CONCAT(
                    '{',
                    '"hsCode" : ', JSON_QUOTE(IFNULL(d.HsCode, '')), ',',
                    '"productDescription" : ', JSON_QUOTE(IFNULL(d.ProductDescription, '')), ',',
                    '"rate" : ', JSON_QUOTE(IFNULL(d.SaleTypeToRateDesc, '')), ',',
                    '"uoM" : ', JSON_QUOTE(IFNULL(d.Uom_Desc, '')), ',',
                    '"quantity" : ', IFNULL(d.Quantity, 0), ',',
                    '"totalValues" : ', IFNULL(d.TotalValues, 0), ',',
                    '"valueSalesExcludingST" : ', IFNULL(d.ValueSalesExcludingST, 0), ',',
                    '"fixedNotifiedValueOrRetailPrice" : ', IFNULL(d.FixedNotifiedValueOrRetailPrice, 0), ',',
                    '"salesTaxApplicable" : ', IFNULL(d.SalesTaxApplicable, 0), ',',
                    '"salesTaxWithheldAtSource" : ', IFNULL(d.SalesTaxWithheldAtSource, 0), ',',
                    '"extraTax" : ', JSON_QUOTE(IFNULL(CAST(d.ExtraTax AS CHAR), '')), ',',
                    '"furtherTax" : ', IFNULL(d.FurtherTax, 0), ',',
                    '"sroScheduleNo" : ', JSON_QUOTE(IFNULL(d.SroScheduleDesc, '')), ',',
                    '"fedPayable" : ', IFNULL(d.FedPayable, 0), ',',
                    '"discount" : ', IFNULL(d.Discount, 0), ',',
                    '"saleType" : ', JSON_QUOTE(IFNULL(d.SaleTypeDesc, '')), ',',
                    '"sroItemSerialNo" : ', JSON_QUOTE(IFNULL(d.SroItemSerialNo, '')),
                    '}'
                )
                SEPARATOR ','
            )
            FROM tbl_sales_invoice_detail d
            WHERE d.InvoiceID = h.ID AND IFNULL(d.Status, 0) != 2
        ), ''),
        ']}'
    ) AS payloadstring,
    b.iris_access_token AS access_token
FROM tbl_sales_invoice_header h
CROSS JOIN (
    SELECT bi.* FROM tbl_business_info bi WHERE bi.IsArchive = 0 ORDER BY bi.ID ASC LIMIT 1
) b
INNER JOIN tbl_customer c ON (c.ID = h.CustomerID)
WHERE h.ID = ?
AND IFNULL(h.Status, 0) != 2
SQL;

        try {
            $row = DB::selectOne($sql, [$headerId]);
        } catch (\Throwable $e) {
            Log::error('FBR payload SQL failed', ['headerId' => $headerId, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Could not build FBR payload: ' . $e->getMessage()];
        }

        if (! $row || empty($row->payloadstring)) {
            return ['success' => false, 'message' => 'No payload: check invoice lines, customer, and business info.'];
        }

        try {
            $payload = json_decode($row->payloadstring, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ['success' => false, 'message' => 'Invalid JSON from payload query: ' . $e->getMessage()];
        }

        $token = trim((string) ($row->access_token ?? ''));
        if ($token === '') {
            return [
                'success' => false,
                'message' => 'FBR bearer token is missing. Set Sandbox_access_token in Business Info.',
            ];
        }

        $url = trim((string) config('services.fbr.post_url'));
        if ($url === '') {
            return [
                'success' => false,
                'message' => 'FBR POST URL is not configured. Set FBR_POST_URL.',
            ];
        }
        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonBody === false) {
            return ['success' => false, 'message' => 'Failed to JSON-encode payload for FBR.'];
        }

        $headers = array_merge([
            // Match working PHP cURL client (gzip/br responses are decoded by Guzzle).
            'Accept-Encoding' => 'gzip, deflate, br',
            'User-Agent' => 'Mozilla/5.0',
        ], array_filter((array) config('services.fbr.extra_headers', [])));

        $timeout = config('services.fbr.timeout', 120);
        if (! is_numeric($timeout) || (float) $timeout < 0) {
            $timeout = 120;
        }

        $verifySsl = (bool) config('services.fbr.verify_ssl', false);
        $guzzleOptions = [
            'verify' => $verifySsl,
            'expect' => false,
            'version' => 1.1,
            'allow_redirects' => [
                'max' => 10,
                'strict' => false,
                'referer' => false,
                'protocols' => ['http', 'https'],
            ],
            'decode_content' => true,
        ];
        if (! $verifySsl) {
            $guzzleOptions['curl'] = [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ];
        }

        try {
            $httpResponse = Http::timeout((float) $timeout)
                ->withOptions($guzzleOptions)
                ->withToken(trim($token))
                ->withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            Log::error('FBR post_to_fbr HTTP exception', [
                'id' => $headerId,
                'error' => $e->getMessage(),
                'type' => $e::class,
            ]);

            $technical = $e->getMessage();
            $friendly = 'The FBR service did not respond in time or the connection failed. Please try again in a few minutes.';
            if (
                str_contains($technical, 'timed out')
                || str_contains($technical, 'Operation timed out')
                || str_contains($technical, 'cURL error 28')
                || str_contains($technical, 'Connection timed out')
            ) {
                $userMsg = $friendly;
            } elseif (config('app.debug')) {
                $userMsg = 'FBR request failed: ' . $technical;
            } else {
                $userMsg = 'Could not reach FBR right now. Please try again later.';
            }

            return ['success' => false, 'message' => $userMsg];
        }

        $rawBody = (string) $httpResponse->body();
        $decoded = $httpResponse->json();
        if (! is_array($decoded) && $rawBody !== '') {
            try {
                $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
            } catch (\JsonException $e) {
                $decoded = null;
            }
        }
        if (! is_array($decoded)) {
            $decoded = null;
        }

        $responseForDb = self::fbrEncodeResponseForDb($decoded, $rawBody);

        $invoiceNumber = $decoded !== null ? self::extractFbrInvoiceNumber($decoded) : null;

        $update = [
            'FbrResponseJson' => $responseForDb,
            'UpdatedOn' => now()->toDateTimeString(),
        ];
        if (auth()->check()) {
            $update['UpdatedBy'] = auth()->id();
        }
        if ($invoiceNumber !== null && $invoiceNumber !== '') {
            $update['FbrTransactionID'] = $invoiceNumber;
        }

        DB::table('tbl_sales_invoice_header')->where('ID', $headerId)->update($update);

        if (! $httpResponse->successful()) {
            return [
                'success' => false,
                'message' => 'FBR returned HTTP ' . $httpResponse->status() . '. Response saved on invoice.',
            ];
        }

        if ($decoded !== null) {
            $vrStatus = data_get($decoded, 'validationResponse.status');
            if (is_string($vrStatus) && strcasecmp($vrStatus, 'Invalid') === 0) {
                $err = (string) data_get($decoded, 'validationResponse.error', 'Validation failed');
                $code = (string) data_get($decoded, 'validationResponse.errorCode', '');

                return [
                    'success' => false,
                    'message' => 'FBR rejected the invoice' . ($code !== '' ? " [{$code}]" : '') . ': ' . $err,
                ];
            }
        }

        if ($invoiceNumber === null || $invoiceNumber === '') {
            return [
                'success' => false,
                'message' => 'FBR responded (HTTP ' . $httpResponse->status() . ') but no invoice / IRN was found in the response. Check FbrResponseJson on the invoice.',
            ];
        }

        return ['success' => true, 'message' => 'Invoice posted to FBR successfully. Transaction ID: ' . $invoiceNumber];
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     */
    private static function fbrEncodeResponseForDb(?array $decoded, string $rawBody): string
    {
        if ($decoded !== null) {
            $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            if ($encoded !== false && $encoded !== '') {
                return $encoded;
            }
        }

        return $rawBody !== '' ? $rawBody : json_encode([
            '_meta' => ['note' => 'Empty FBR response body', 'length' => strlen($rawBody)],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private static function extractFbrInvoiceNumber(array $json): ?string
    {
        $paths = [
            'invoiceNumber',
            'data.invoiceNumber',
            'validationResponse.invoiceNumber',
            'validationResponse.draftInvoiceNumber',
            'validationResponse.irn',
            'data.validationResponse.invoiceNumber',
            'data.validationResponse.irn',
        ];
        foreach ($paths as $p) {
            $v = data_get($json, $p);
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
        }

        return self::fbrDeepFindInvoiceId($json);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function fbrDeepFindInvoiceId(array $node, int $depth = 0): ?string
    {
        if ($depth > 14) {
            return null;
        }

        foreach (['invoiceNumber', 'draftInvoiceNumber', 'irn', 'IRN', 'InvoiceNumber'] as $k) {
            if (array_key_exists($k, $node)) {
                $v = $node[$k];
                if ($v !== null && $v !== '' && is_scalar($v)) {
                    return (string) $v;
                }
            }
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $found = self::fbrDeepFindInvoiceId($child, $depth + 1);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}