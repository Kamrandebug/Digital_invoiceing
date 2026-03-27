<?php

namespace App\Models;

use Carbon\Carbon;
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
        $query = "SELECT ID, DisplayName, FirstName, BusinessName
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
     */
    public static function Load_InvoiceTypes()
    {
        try {
            $schema    = 'core_digital_invoicing';
            $baseTable = 'fbr_doc_type_code';
            $fullTable = $schema . '.' . $baseTable;

            $columns = DB::table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $baseTable)
                ->pluck('column_name');

            $idCol   = null;
            $descCol = null;
            foreach ($columns as $col) {
                $lc = strtolower($col);
                if ($idCol === null && (strpos($lc, 'type') !== false && strpos($lc, 'id') !== false)) {
                    $idCol = $col;
                }
                if ($descCol === null && strpos($lc, 'desc') !== false) {
                    $descCol = $col;
                }
            }
            $idCol   = $idCol ?: 'docTypeId';
            $descCol = $descCol ?: 'docDescription';

            return DB::table(DB::raw($fullTable))
                ->select([$idCol . ' as docTypeId', $descCol . ' as docDescription'])
                ->orderBy($descCol)
                ->get();
        } catch (\Throwable $e) {
            \Log::warning('Load_InvoiceTypes failed: ' . $e->getMessage());
            return collect();
        }
    }

   // ══════════════════════════════════════════════════════════
    //  LISTING
    // ══════════════════════════════════════════════════════════

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
                DB::raw("
                    COALESCE(NULLIF(TRIM(cust.DisplayName),''),
                        CONCAT(COALESCE(cust.FirstName,''),' ',COALESCE(cust.LastName,''))
                    ) AS CustomerDisplayName
                ")
            )
            ->where('h.Status', '!=', 2); // Hide Archived Invoices

        // 2. Search Filter
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('h.DocNo', 'LIKE', "%{$s}%")
                  ->orWhereRaw("
                      COALESCE(NULLIF(TRIM(cust.DisplayName),''),
                          CONCAT(COALESCE(cust.FirstName,''),' ',COALESCE(cust.LastName,'')))
                      LIKE ?", ["%{$s}%"]);
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
     * POST invoice JSON to FBR, then persist invoiceNumber → FbrTransactionID and full body → FbrResponseJson.
     *
     * @return array{success: bool, message: string}
     */
    public static function post_to_fbr(int $headerId): array
    {
        $header = DB::table('tbl_sales_invoice_header')
            ->where('ID', $headerId)
            ->where('Status', '!=', 2)
            ->first();

        if (!$header) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        $existingFbr = trim((string) ($header->FbrTransactionID ?? ''));
        if ($existingFbr !== '') {
            return ['success' => false, 'message' => 'This invoice was already posted to FBR.'];
        }

        $business = DB::table('tbl_business_info')
            ->where('IsArchive', 0)
            ->orderBy('ID')
            ->first();

        if (!$business) {
            return [
                'success' => false,
                'message' => 'Business record not found. Add business info before posting to FBR.',
            ];
        }

        $token = trim((string) ($business->Sandbox_access_token ?? ''));

        if ($token === '') {
            return [
                'success' => false,
                'message' => 'FBR bearer token is missing. Set Sandbox_access_token in Business Info (tbl_business_info).',
            ];
        }

        $customer = null;
        if (!empty($header->CustomerID)) {
            $customer = DB::table('tbl_customer')->where('ID', $header->CustomerID)->first();
        }

        $lines = DB::table('tbl_sales_invoice_detail')
            ->where('InvoiceID', $headerId)
            ->where('Status', '!=', 2)
            ->orderBy('ID')
            ->get();

        if ($lines->isEmpty()) {
            return ['success' => false, 'message' => 'No line items to post.'];
        }

        $invoiceDate = $header->InvoiceDate
            ? Carbon::parse($header->InvoiceDate)->format('Y-m-d')
            : Carbon::now()->format('Y-m-d');

        $invoiceTypeLabel = self::resolveInvoiceTypeLabel($header->InvoiceType ?? null);

        $buyerNtn = self::nz($header->BuyerNTNCNIC ?? null)
            ?: self::nz($customer->NTN ?? null)
            ?: self::nz($customer->CNIC ?? null)
            ?: '';

        $buyerName = self::nz($header->BuyerBusinessName ?? null)
            ?: self::nz($customer->DisplayName ?? null)
            ?: trim((string) (($customer->FirstName ?? '') . ' ' . ($customer->LastName ?? '')));

        $buyerProvince = self::nz($header->BuyerProvince ?? null)
            ?: self::nz($customer->County ?? null)
            ?: self::nz($customer->City ?? null)
            ?: '';

        $buyerAddress = self::nz($header->BuyerAddress ?? null)
            ?: self::nz($customer->Address ?? null)
            ?: '';

        $sellerName = $business->BusinessName ?? '';
        $sellerNtn = self::nz($business->NTN ?? null) ?: self::nz($business->SalesTaxNumber ?? null) ?: '';
        $sellerAddress = self::nz($business->Address ?? null) ?: '';
        // TODO: load from business / settings when available
        $sellerProvince = 'Sindh';

        $payload = [
            'invoiceType' => $invoiceTypeLabel,
            'invoiceDate' => $invoiceDate,
            'sellerBusinessName' => $sellerName,
            'sellerProvince' => $sellerProvince,
            'sellerNTNCNIC' => $sellerNtn,
            'sellerAddress' => $sellerAddress,
            'buyerNTNCNIC' => $buyerNtn,
            'buyerBusinessName' => $buyerName,
            'buyerProvince' => $buyerProvince,
            'buyerAddress' => $buyerAddress,
            'invoiceRefNo' => self::nz($header->InvoiceRefNo ?? null),
            'scenarioId' => self::nz($header->ScenarioId ?? null) ?: 'SN004',
            'buyerRegistrationType' => self::nz($header->BuyerRegistrationType ?? null) ?: 'Unregistered',
            'items' => $lines->map(fn ($line) => self::mapDetailLineToFbrItem($line))->values()->all(),
        ];
        $url = config('services.fbr.post_url');

        try {
            $httpResponse = Http::timeout(120)
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error('FBR post_to_fbr HTTP exception', ['id' => $headerId, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'FBR request failed: ' . $e->getMessage()];
        }

        $rawBody = $httpResponse->body();
        $decoded = null;
        if ($rawBody !== '') {
            try {
                $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $decoded = null;
            }
        }

        $responseForDb = $decoded !== null ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : $rawBody;

        $invoiceNumber = null;
        if (is_array($decoded)) {
            $invoiceNumber = self::extractFbrInvoiceNumber($decoded);
        }

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

        if (!$httpResponse->successful()) {
            return [
                'success' => false,
                'message' => 'FBR returned HTTP ' . $httpResponse->status() . '. Response saved on invoice.',
            ];
        }

        if ($invoiceNumber === null || $invoiceNumber === '') {
            return [
                'success' => false,
                'message' => 'FBR responded but invoiceNumber was not found in the response. Full response saved on invoice.',
            ];
        }

        return ['success' => true, 'message' => 'Invoice posted to FBR successfully. Transaction ID: ' . $invoiceNumber];
    }

    private static function nz(?string $v): string
    {
        $v = trim((string) $v);

        return $v;
    }

    private static function resolveInvoiceTypeLabel($invoiceType): string
    {
        if ($invoiceType === null || $invoiceType === '') {
            return 'Sale Invoice';
        }
        if (is_numeric($invoiceType)) {
            try {
                $row = DB::table('core_digital_invoicing.fbr_doc_type_code')
                    ->where('docTypeId', (int) $invoiceType)
                    ->first();
                if ($row && isset($row->docDescription) && $row->docDescription !== '') {
                    return (string) $row->docDescription;
                }
            } catch (\Throwable $e) {
                return 'Sale Invoice';
            }

            return 'Sale Invoice';
        }

        return (string) $invoiceType;
    }

    private static function mapDetailLineToFbrItem(object $line): array
    {
        $qty = (float) ($line->Quantity ?? 0);
        $ex = (float) ($line->ValueSalesExcludingST ?? $line->Amount ?? 0);
        $st = (float) ($line->SalesTaxApplicable ?? $line->GSTAmount ?? 0);
        $extra = $line->ExtraTax ?? null;
        $extraOut = ($extra === null || $extra === '') ? '' : $extra;

        return [
            'hsCode' => (string) ($line->HsCode ?? ''),
            'productDescription' => (string) ($line->ProductDescription ?? ''),
            'rate' => self::formatFbrRatePercent($line, $ex, $st),
            'uoM' => (string) (($line->Uom_Desc ?? '') !== '' ? $line->Uom_Desc : 'Numbers, pieces, units'),
            'quantity' => $qty,
            'totalValues' => (float) ($line->TotalValues ?? 0),
            'valueSalesExcludingST' => $ex,
            'fixedNotifiedValueOrRetailPrice' => (float) ($line->FixedNotifiedValueOrRetailPrice ?? 0),
            'salesTaxApplicable' => $st,
            'salesTaxWithheldAtSource' => (float) ($line->SalesTaxWithheldAtSource ?? 0),
            'extraTax' => $extraOut,
            'furtherTax' => (float) ($line->FurtherTax ?? 0),
            'sroScheduleNo' => (string) ($line->SroScheduleDesc ?? $line->SroScheduleID ?? ''),
            'fedPayable' => (float) ($line->FedPayable ?? 0),
            'discount' => (float) ($line->Discount ?? 0),
            'saleType' => (string) (($line->SaleTypeDesc ?? '') !== '' ? $line->SaleTypeDesc : 'Goods at standard rate (default)'),
            'sroItemSerialNo' => (string) ($line->SroItemSerialNo ?? ''),
        ];
    }

    private static function formatFbrRatePercent(object $line, float $ex, float $st): string
    {
        $gp = $line->GstPercentage ?? null;
        if ($gp !== null && $gp !== '') {
            $num = is_numeric($gp)
                ? (float) $gp
                : (float) preg_replace('/[^0-9.]/', '', (string) $gp);

            return self::trimPercentString($num) . '%';
        }
        if ($ex > 0 && $st > 0) {
            return self::trimPercentString(round(($st / $ex) * 100, 2)) . '%';
        }

        return '0%';
    }

    private static function trimPercentString(float $n): string
    {
        $s = number_format($n, 4, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');

        return $s === '' ? '0' : $s;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private static function extractFbrInvoiceNumber(array $json): ?string
    {
        $paths = [
            'invoiceNumber',
            'data.invoiceNumber',
            'response.invoiceNumber',
            'result.invoiceNumber',
            'Data.invoiceNumber',
        ];
        foreach ($paths as $p) {
            $v = data_get($json, $p);
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
        }

        return null;
    }
}