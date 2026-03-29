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
        $query = "SELECT ID, DisplayName, FirstName, LastName, BusinessName
                  FROM tbl_customer
                  WHERE Status = 1";

        return $query;
    }

    public static function Load_Product()
    {
        // Product dropdown for invoice: include tax/FBR fields for auto-fill on line item.
        $query = "SELECT ID, Name AS ProductName, SalePrice, 0 AS GstPercentage,
                  HsCode, SaleInformation, UomID, SaleTypeID, SaleTypeToRateID, SroScheduleID, SroItemID
                  FROM tbl_product
                  WHERE Status = 1";

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
                'h.DiscountAmount as TotalDiscount',
                'h.GSTAmount      as TotalGST',
                'h.Total          as TotalAmount',
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
     * Execute save: run multiple queries (header + detail) from tbl_temp_invoice_detail for the given RequestID.
     * No return; add more DB::unprepared() calls here for any extra queries you need.
     */
    public static function save_transaction($requestID)
    {
        // -- 1. Insert into tbl_sales_invoice_header
        DB::unprepared("
            INSERT INTO tbl_sales_invoice_header (InvoiceID,InvoiceNumber,InvoiceType,InvoiceDate,DocNo,DocNo2,DocNo3,CustomerID,BuyerNTNCNIC,BuyerBusinessName,BuyerProvince,BuyerAddress,InvoiceRefNo,BuyerRegistrationType,ScenarioId,TotalValues,ValueSalesExcludingST,FixedNotifiedValueOrRetailPrice,SalesTaxApplicable,SalesTaxWithheldAtSource,ExtraTax,FurtherTax,FedPayable,Discount,Statusi,CreatedBy,CreatedOn,UpdatedBy,UpdatedOn)
            SELECT InvoiceID,1 AS InvoiceNumber,InvoiceType,InvoiceDate,DocNo,DocNo2,DocNo3,CustomerID,''AS BuyerNTNCNIC,''AS BuyerBusinessName,''AS BuyerProvince,''AS BuyerAddress,''AS InvoiceRefNo,''AS BuyerRegistrationType,''AS ScenarioId,TotalValues,ValueSalesExcludingST,FixedNotifiedValueOrRetailPrice,SalesTaxApplicable,SalesTaxWithheldAtSource,ExtraTax,FurtherTax,FedPayable,Discount,Statusi,CreatedBy,CreatedOn,UpdatedBy,UpdatedOn
            FROM tbl_temp_invoice_detail
            WHERE RequestID = '$requestID'
            Group BY RequestID
        ");

        // -- 2. Insert into tbl_sales_invoice_detail
        DB::unprepared("
            INSERT INTO `tbl_sales_invoice_detail` (InvoiceID,InvoiceDate,CustomerID,ProductID,Quantity,Rate,Amount,Unit,Weight,HsCode,ProductDescription,UomID,Uom_Desc,SaleTypeID,SaleTypeDesc,SaleTypeToRateID,SaleTypeToRateDesc,SroScheduleID,SroScheduleDesc,SroItemID,SroItemSerialNo,TotalValues,ValueSalesExcludingST,FixedNotifiedValueOrRetailPrice,SalesTaxApplicable,SalesTaxWithheldAtSource,ExtraTax,FurtherTax,FedPayable,Discount,CreatedBy,CreatedOn,UpdatedBy,UpdatedOn)
            SELECT InvoiceID,InvoiceDate,CustomerID,ProductID,Quantity,Rate,Amount,Unit,Weight,HsCode,ProductDescription,UomID,'' AS Uom_Desc,
                SaleTypeID,'' AS SaleTypeDesc,SaleTypeToRateID,'' AS SaleTypeToRateDesc,SroScheduleID,'' AS SroScheduleDesc,
                SroItemID,'' AS SroItemSerialNo,
                TotalValues,ValueSalesExcludingST,FixedNotifiedValueOrRetailPrice,SalesTaxApplicable,SalesTaxWithheldAtSource,ExtraTax,FurtherTax,FedPayable,Discount,
                CreatedBy,CreatedOn,UpdatedBy,UpdatedOn
            FROM `tbl_temp_invoice_detail` t
            WHERE t.RequestID = '$requestID'
        ");
    }

    public static function edit_transaction($requestID)
    {
        DB::unprepared("
            UPDATE tbl_sales_invoice_header uu
            JOIN (
                SELECT CustomerID, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn, TID
                FROM tbl_temp_invoice_detail
                WHERE RequestID = '$requestID'
            ) u ON uu.ID = u.TID
            SET
                uu.CustomerID = u.CustomerID,
                uu.UpdatedBy = u.UpdatedBy,
                uu.UpdatedOn = u.UpdatedOn
        ");
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
        '"scenarioId" : ', JSON_QUOTE(IF(c.IsFiler = 1, 'SN001', 'SN002')), ',',
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
    b.Sandbox_access_token AS access_token
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

        $url = config('services.fbr.post_url');
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
            Log::error('FBR post_to_fbr HTTP exception', ['id' => $headerId, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'FBR request failed: ' . $e->getMessage()];
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