<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Customer extends Model
{
    use HasFactory;

    public const PARTY_TYPE_CUSTOMER = 1;

    public const PARTY_TYPE_SUPPLIER = 2;

    public const PARTY_TYPE_BOTH = 3;

    protected $table = 'temp_customer';
    protected $primaryKey = 'ID';
    
    // Custom timestamps
    public $timestamps = false;

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'TID',
        'IsArchive',
        'VoucherID',
        'AccountNo',
        'PartyType',
        'Title',
        'FirstName',
        'LastName',
        'Email',
        'BusinessName',
        'PartyBalance',
        'Mobile',
        'Phone',
        'DisplayName',
        'Website',
        'Address',
        'City',
        'Country',
        'County',
        'PostalCode',
        'BillingAddress',
        'BillingCity',
        'BillingCountry',
        'BillingCounty',
        'BillingPostalCode',
        'FullBillingAddress',
        'NTN',
        'SalesTtaxNumber',
        'IsFiler',
        'RegisterType',
        'FilerStatus',
        'FilerStatusDate',
        'Notes',
        'PaymentTerms',
        'Status',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn',
        'SameAsBilling',
        'CNIC',
        'IsAdvanceTax',
        'AdvanceTax',
        'CreditLimit',
        'FieldA',
        'FieldB',
        'FieldC',
        'FieldD',
        'Field1',
        'Field2',
        'Field3',
        'Field4',
        'Typei',
        'Type',
        'IsDashboard',
        'RequestID',
    ];

  public static function getlistingdata($filters = [])
{
$query = DB::table('tbl_customer')->where('Status', '!=', 2);

    if (! empty($filters['party_types'])) {
        $partyTypes = array_map('intval', (array) $filters['party_types']);
        if (! empty($filters['party_include_null'])) {
            $query->where(function ($q) use ($partyTypes) {
                $q->whereIn('PartyType', $partyTypes)->orWhereNull('PartyType');
            });
        } else {
            $query->whereIn('PartyType', $partyTypes);
        }
    }

    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function($q) use ($search) {
            $q->where('DisplayName',   'LIKE', "%{$search}%")
              ->orWhere('BusinessName', 'LIKE', "%{$search}%")
              ->orWhere('AccountNo',    'LIKE', "%{$search}%")
              ->orWhere('FirstName',    'LIKE', "%{$search}%")
              ->orWhere('LastName',     'LIKE', "%{$search}%")
              ->orWhere('Address',      'LIKE', "%{$search}%")
              ->orWhere('Mobile',       'LIKE', "%{$search}%")
              ->orWhere('Phone',        'LIKE', "%{$search}%");
        });
    }

    if (!empty($filters['start_date'])) {
        $query->whereDate('CreatedOn', '>=', $filters['start_date']);
    }

    if (!empty($filters['end_date'])) {
        $query->whereDate('CreatedOn', '<=', $filters['end_date']);
    }

    return $query->orderBy('CreatedOn', 'desc');
}

    /**
     * Execute save: insert from temp_customer into tbl_customer.
     * Returns status and error message (if any temp row failed validation).
     */
    public static function save_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';

        DB::statement("
            UPDATE `temp_customer` uu
            JOIN
            (
                SELECT 	t.ID,
                    CASE
                    WHEN t.BusinessName IS NULL OR TRIM(t.BusinessName) = '' 
                        THEN 'Missing Business Name'
                    WHEN t.County IS NULL OR TRIM(t.County) = '' 
                        THEN 'Missing Province'
                    WHEN t.AccountNo IS NULL OR TRIM(t.AccountNo) = '' 
                        THEN 'Missing AccountNo'
                    WHEN t.Status NOT IN (0, 1) 
                        THEN 'Invalid Status (Must be 1 for Active or 0 for InActive)'
                    WHEN (
                        SELECT COUNT(*) 
                        FROM temp_customer tc 
                        WHERE tc.RequestID = t.RequestID 
                          AND LOWER(TRIM(tc.AccountNo)) = LOWER(TRIM(t.AccountNo))
                    ) > 1 
                        THEN CONCAT('Duplicate AccountNo \'', TRIM(t.AccountNo), '\' in batch')
                    WHEN EXISTS (
                        SELECT 1 
                        FROM tbl_customer cust 
                        WHERE LOWER(TRIM(cust.AccountNo)) = LOWER(TRIM(t.AccountNo))
                          AND cust.Status IN (0, 1)
                    ) 
                        THEN CONCAT('AccountNo \'', TRIM(t.AccountNo), '\' already exists')
                    ELSE 'Pass' END AS `ErrMsg`
                FROM `temp_customer` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET	uu.ErrMsg = u.ErrMsg;
        ", [$requestID]);

        $failedRows = DB::table('temp_customer')
            ->where('RequestID', $requestID)
            ->whereNotNull('ErrMsg')
            ->where('ErrMsg', '<>', 'Pass')
            ->orderBy('ExcelRow', 'asc')
            ->orderBy('ID', 'asc')
            ->get(['ID', 'ExcelRow', 'ErrMsg']);

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

        DB::statement("
            INSERT INTO tbl_customer (AccountNo,PartyType,Title,FirstName,LastName,BusinessName,DisplayName,Email,Mobile,Phone,Website,Address,City,County,PostalCode,Country,
                SameAsBilling,BillingAddress,BillingCity,BillingCounty,BillingCountry,BillingPostalCode,FullBillingAddress,NTN,SalesTaxNumber,CNIC,IsFiler,RegisterType,FilerStatus,FilerStatusDate,IsAdvanceTax,AdvanceTax,
                Notes,`SalesTaxWithheldAtSource`,`ExtraTax`,`FurtherTax`,`FedPayable`,`Status`,CreatedBy,CreatedOn)
            SELECT 	AccountNo,PartyType,Title,FirstName,LastName,BusinessName,DisplayName,Email,Mobile,Phone,Website,Address,City,County,PostalCode,Country,
                SameAsBilling,BillingAddress,BillingCity,BillingCounty,BillingCountry,BillingPostalCode,FullBillingAddress,NTN,SalesTaxNumber,CNIC,IsFiler,RegisterType,FilerStatus,FilerStatusDate,IsAdvanceTax,AdvanceTax,
                Notes,`SalesTaxWithheldAtSource`,`ExtraTax`,`FurtherTax`,`FedPayable`,`Status`,CreatedBy,CreatedOn
            FROM 	temp_customer
            WHERE RequestID = ? AND ErrMsg = 'Pass'
        ", [$requestID]);

        try {
            $accountNos = DB::connection('tenant')->table('temp_customer')
                ->where('RequestID', $requestID)
                ->where('ErrMsg', 'Pass')
                ->pluck('AccountNo')
                ->filter()
                ->all();

            if (!empty($accountNos)) {
                $insertedRecords = DB::connection('tenant')->table('tbl_customer')
                    ->whereIn('AccountNo', $accountNos)
                    ->get();
                foreach ($insertedRecords as $inserted) {
                    $module = ((int)$inserted->PartyType === 2) ? 'SUPPLIER' : 'CUSTOMER';
                    \App\Services\AuditLogService::log('CREATE', $module, $inserted->ID, null, (array)$inserted);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log customer creation: ' . $e->getMessage());
        }

        return [
            'status' => true,
            'message' => null,
        ];
    }

    /**
     * Execute edit: update tbl_customer from temp_customer.
     * Returns status and error message (if any temp row failed validation).
     */
    public static function edit_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';

        DB::statement("
            UPDATE `temp_customer` uu
            JOIN
            (
                SELECT 	t.ID,
                    CASE
                    WHEN t.BusinessName IS NULL OR t.BusinessName = '' THEN 'Invalid Business Name'
                    ELSE 'Pass' END AS `ErrMsg`
                FROM `temp_customer` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET	uu.ErrMsg = u.ErrMsg;
        ", [$requestID]);

        $errorSummary = DB::selectOne("
            SELECT
                IFNULL(COUNT(*),0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrMsg ORDER BY t.ErrMsg SEPARATOR ', ') AS ErrMsg
            FROM `temp_customer` t
            WHERE t.`ErrMsg` IS NOT NULL
              AND t.`ErrMsg` <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrMsg ?: 'Unable to update customer due to invalid data.',
            ];
        }

        // Fetch old values before updating
        $oldRecord = null;
        try {
            $tempRow = DB::connection('tenant')->table('temp_customer')->where('RequestID', $requestID)->first();
            if ($tempRow && !empty($tempRow->TID)) {
                $oldRecord = DB::connection('tenant')->table('tbl_customer')->where('ID', $tempRow->TID)->first();
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch old customer details for audit: ' . $e->getMessage());
        }

        DB::statement("
            UPDATE tbl_customer uu
            JOIN (
                SELECT TID,AccountNo,Title,FirstName,LastName,BusinessName,DisplayName,Email,Mobile,Phone,Website,Address,City,County,PostalCode,Country,SameAsBilling,
			BillingAddress,BillingCity,BillingCounty,BillingCountry,BillingPostalCode,FullBillingAddress,
			NTN,SalesTaxNumber,CNIC,IsFiler,RegisterType,FilerStatus,FilerStatusDate,IsAdvanceTax,AdvanceTax,Notes,`SalesTaxWithheldAtSource`,`ExtraTax`,`FurtherTax`,`FedPayable`,
			`Status`,UpdatedBy,UpdatedOn
                FROM temp_customer
                WHERE RequestID = ?
            ) u ON uu.ID = u.TID
            SET
                uu.AccountNo = u.AccountNo,
                uu.Title = u.Title,
                uu.FirstName = u.FirstName,
                uu.LastName = u.LastName,
                uu.BusinessName = u.BusinessName,
                uu.DisplayName = u.DisplayName,
                uu.Email = u.Email,
                uu.Mobile = u.Mobile,
                uu.Phone = u.Phone,
                uu.Website = u.Website,
                uu.Address = u.Address,
                uu.City = u.City,
                uu.County = u.County,
                uu.PostalCode = u.PostalCode,
                uu.Country = u.Country,
                uu.SameAsBilling = u.SameAsBilling,
                uu.BillingAddress = u.BillingAddress,
                uu.BillingCity = u.BillingCity,
                uu.BillingCounty = u.BillingCounty,
                uu.BillingCountry = u.BillingCountry,
                uu.BillingPostalCode = u.BillingPostalCode,
                uu.FullBillingAddress = u.FullBillingAddress,
                uu.NTN = u.NTN,
                uu.CNIC = u.CNIC,
                uu.SalesTaxNumber = u.SalesTaxNumber,
                uu.IsFiler = u.IsFiler,
                uu.RegisterType = u.RegisterType,
                uu.FilerStatus = u.FilerStatus,
                uu.FilerStatusDate = u.FilerStatusDate,
                uu.IsAdvanceTax = u.IsAdvanceTax,
                uu.AdvanceTax = u.AdvanceTax,
                uu.Notes = u.Notes,
                uu.SalesTaxWithheldAtSource = u.SalesTaxWithheldAtSource,
                uu.ExtraTax = u.ExtraTax,
                uu.FurtherTax = u.FurtherTax,
                uu.FedPayable = u.FedPayable,
                uu.Status = u.Status,
                uu.UpdatedBy = u.UpdatedBy,
                uu.UpdatedOn = u.UpdatedOn
        ", [$requestID]);

        // Fetch new values after updating
        try {
            if ($oldRecord) {
                $newRecord = DB::connection('tenant')->table('tbl_customer')->where('ID', $oldRecord->ID)->first();
                if ($newRecord) {
                    $module = ((int)$newRecord->PartyType === 2) ? 'SUPPLIER' : 'CUSTOMER';
                    \App\Services\AuditLogService::log('UPDATE', $module, $oldRecord->ID, (array)$oldRecord, (array)$newRecord);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log customer edit: ' . $e->getMessage());
        }

        return [
            'status' => true,
            'message' => null,
        ];
    }

    /**
     * Execute delete: soft-update tbl_customer status from temp_customer. No return; runs query inside.
     */
    public static function delete_transaction($validated = [])
    {
        $requestID = $validated['RequestID'] ?? '';

        // Fetch old values before deleting
        $oldRecord = null;
        $customerId = null;
        try {
            $tempRow = DB::connection('tenant')->table('temp_customer')->where('RequestID', $requestID)->first();
            if ($tempRow && !empty($tempRow->TID)) {
                $customerId = $tempRow->TID;
                $oldRecord = DB::connection('tenant')->table('tbl_customer')->where('ID', $customerId)->first();
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch old customer details for delete audit: ' . $e->getMessage());
        }

        DB::unprepared("
            UPDATE tbl_customer uu
            JOIN (
                SELECT TID, 2 AS Status, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn
                FROM temp_customer
                WHERE RequestID = '$requestID'
            ) u ON uu.ID = u.TID
            SET
                uu.Status = u.Status,
                uu.UpdatedBy = u.UpdatedBy,
                uu.UpdatedOn = u.UpdatedOn
        ");

        try {
            if ($customerId && $oldRecord) {
                $module = ((int)$oldRecord->PartyType === 2) ? 'SUPPLIER' : 'CUSTOMER';
                \App\Services\AuditLogService::log('DELETE', $module, $customerId, (array)$oldRecord, null);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log customer deletion: ' . $e->getMessage());
        }
    }


    /**
     * Verify customer NTN against FBR Get_Reg_Type and statl (ATL) APIs.
     *
     * @return array{success: bool, message?: string, register_type?: string|null, filer_status?: string|null, filer_status_date?: string|null, is_filer?: int}
     */
    public static function verify_fbr(string $registrationNo, ?string $statusDate = null): array
    {
        $regNo = preg_replace('/\s+/', '', trim($registrationNo));
        if ($regNo === '') {
            return ['success' => false, 'message' => 'NTN / registration number is required for FBR verification.'];
        }

        $statusDate = $statusDate ?: now()->toDateString();
        try {
            $statusDate = \Carbon\Carbon::parse($statusDate)->toDateString();
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Invalid verification date.'];
        }

        $token = self::fbrAccessToken();
        if ($token === '') {
            return [
                'success' => false,
                'message' => 'FBR bearer token is missing. Set Sandbox or IRIS access token in Business Info.',
            ];
        }

        $regResult = self::fbrPostJson(
            (string) config('services.fbr.get_reg_type_url'),
            ['Registration_No' => $regNo],
            $token
        );
        if (! ($regResult['ok'] ?? false)) {
            return ['success' => false, 'message' => $regResult['message'] ?? 'FBR registration lookup failed.'];
        }

        $atlResult = self::fbrPostJson(
            (string) config('services.fbr.statl_url'),
            ['regno' => $regNo, 'date' => $statusDate],
            $token
        );
        if (! ($atlResult['ok'] ?? false)) {
            return ['success' => false, 'message' => $atlResult['message'] ?? 'FBR ATL (statl) lookup failed.'];
        }

        $regBody = $regResult['body'] ?? [];
        $atlBody = $atlResult['body'] ?? [];

        $registerType = self::normalizeRegisterType(
            self::arrayValue($regBody, ['REGISTRATION_TYPE', 'registration_type'])
        );
        if ($registerType === null) {
            $statusCode = self::arrayValue($regBody, ['statuscode', 'status code', 'StatusCode']);
            $registerType = self::registerTypeFromStatusCode($statusCode);
        }

        $filerStatus = self::normalizeFilerStatus(
            self::arrayValue($atlBody, ['status', 'Status'])
        );

        if ($registerType === null && $filerStatus === null) {
            return [
                'success' => false,
                'message' => 'FBR returned an unexpected response. Please try again.',
            ];
        }

        $isFiler = $filerStatus === 'Active' ? 1 : ($filerStatus === 'In-Active' ? 0 : null);

        return [
            'success' => true,
            'message' => 'FBR verification completed.',
            'register_type' => $registerType,
            'filer_status' => $filerStatus,
            'filer_status_date' => $statusDate,
            'is_filer' => $isFiler,
            'registration_no' => self::arrayValue($regBody, ['REGISTRATION_NO', 'registration_no']) ?: $regNo,
        ];
    }

    private static function fbrAccessToken(): string
    {
        $row = DB::table('tbl_business_info')
            ->where('IsArchive', 0)
            ->orderBy('ID')
            ->first(['iris_access_token', 'Sandbox_access_token']);

        if (! $row) {
            return '';
        }

        $iris = trim((string) ($row->iris_access_token ?? ''));
        if ($iris !== '') {
            return $iris;
        }

        return trim((string) ($row->Sandbox_access_token ?? ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message?: string, body?: array<string, mixed>|null}
     */
    private static function fbrPostJson(string $url, array $payload, string $token): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['ok' => false, 'message' => 'FBR API URL is not configured.'];
        }

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonBody === false) {
            return ['ok' => false, 'message' => 'Failed to encode FBR request.'];
        }

        $timeout = config('services.fbr.timeout', 120);
        if (! is_numeric($timeout) || (float) $timeout < 1) {
            $timeout = 120;
        }

        $verifySsl = (bool) config('services.fbr.verify_ssl', false);
        $guzzleOptions = [
            'verify' => $verifySsl,
            'expect' => false,
            'version' => 1.1,
            'decode_content' => true,
        ];
        if (! $verifySsl) {
            $guzzleOptions['curl'] = [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ];
        }

        $headers = array_merge([
            'Accept-Encoding' => 'gzip, deflate, br',
            'User-Agent' => 'Mozilla/5.0',
            'Content-Type' => 'application/json',
        ], array_filter((array) config('services.fbr.extra_headers', [])));

        try {
            $httpResponse = Http::timeout((float) $timeout)
                ->withOptions($guzzleOptions)
                ->withToken($token)
                ->withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            Log::error('FBR customer verify HTTP exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            $technical = $e->getMessage();
            if (
                str_contains($technical, 'timed out')
                || str_contains($technical, 'cURL error 28')
            ) {
                $userMsg = 'The FBR service did not respond in time. Please try again.';
            } elseif (config('app.debug')) {
                $userMsg = 'FBR request failed: ' . $technical;
            } else {
                $userMsg = 'Could not reach FBR right now. Please try again later.';
            }

            return ['ok' => false, 'message' => $userMsg];
        }

        $rawBody = (string) $httpResponse->body();
        $decoded = $httpResponse->json();
        if (! is_array($decoded) && $rawBody !== '') {
            try {
                $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
            } catch (\JsonException) {
                $decoded = null;
            }
        }

        if (! is_array($decoded)) {
            return [
                'ok' => false,
                'message' => $httpResponse->successful()
                    ? 'Invalid JSON from FBR.'
                    : 'FBR returned HTTP ' . $httpResponse->status() . '.',
            ];
        }

        if (! $httpResponse->successful()) {
            return [
                'ok' => false,
                'message' => 'FBR returned HTTP ' . $httpResponse->status() . '.',
                'body' => $decoded,
            ];
        }

        return ['ok' => true, 'body' => $decoded];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private static function arrayValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            foreach ($data as $k => $v) {
                if (strcasecmp((string) $k, $key) === 0 && is_scalar($v)) {
                    $s = trim((string) $v);

                    return $s === '' ? null : $s;
                }
            }
        }

        return null;
    }

    private static function normalizeRegisterType(?string $apiType): ?string
    {
        if ($apiType === null) {
            return null;
        }

        $t = strtolower(str_replace([' ', '_'], '', $apiType));
        if ($t === 'registered') {
            return 'Registered';
        }
        if ($t === 'unregistered') {
            return 'Un-Registered';
        }

        return null;
    }

    private static function registerTypeFromStatusCode(?string $statusCode): ?string
    {
        $code = trim((string) $statusCode);
        if ($code === '00') {
            return 'Registered';
        }
        if ($code === '01') {
            return 'Un-Registered';
        }

        return null;
    }

    private static function normalizeFilerStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $s = strtolower(str_replace(' ', '', trim($status)));
        if ($s === 'active') {
            return 'Active';
        }
        if ($s === 'inactive' || $s === 'in-active') {
            return 'In-Active';
        }

        return trim($status) !== '' ? trim($status) : null;
    }

    /**
     * Active scope
     */
    public function scopeActive($query)
    {
        return $query->where('IsArchive', 0);
    }

    /**
     * With archived scope
     */
    public function scopeWithArchived($query)
    {
        return $query; 
    }
    
    // Name accessor
    public function getNameAttribute()
    {
        return $this->DisplayName ?? $this->BusinessName ?? trim($this->FirstName . ' ' . $this->LastName);
    }

    /**
     * Raw value from $this->attributes matching a DB column name (case-insensitive).
     * Needed after setRawAttributes(DB::table()->first()) because PDO key casing can vary.
     */
    private function rawAttributeMatching(string $canonical): mixed
    {
        foreach ($this->attributes as $key => $value) {
            if (strcasecmp((string) $key, $canonical) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Forms use "IsAdvancedTax"; DB column is IsAdvanceTax.
     */
    public function getIsAdvancedTaxAttribute(): mixed
    {
        return $this->rawAttributeMatching('IsAdvanceTax')
            ?? $this->rawAttributeMatching('IsAdvancedTax');
    }

    /**
     * Forms use "AdvanceTaxRate"; DB column is AdvanceTax.
     */
    public function getAdvanceTaxRateAttribute(): mixed
    {
        return $this->rawAttributeMatching('AdvanceTax')
            ?? $this->rawAttributeMatching('AdvanceTaxRate');
    }

    protected $casts = [
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
        'IsFiler' => 'boolean',
        'IsAdvanceTax' => 'boolean',
        'AdvanceTax' => 'decimal:2',
        'SameAsBilling' => 'boolean',
        'IsDashboard' => 'boolean',
        'IsArchive' => 'integer',
        'Status' => 'integer',
        'PartyBalance' => 'decimal:2',
        'CreditLimit' => 'decimal:2',
    ];
}
