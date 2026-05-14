<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;

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

    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function($q) use ($search) {
            $q->where('DisplayName',   'LIKE', "%{$search}%")
              ->orWhere('BusinessName', 'LIKE', "%{$search}%")
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
                'message' => $errorSummary->ErrMsg ?: 'Unable to add customer due to invalid data.',
            ];
        }

        DB::statement("
            INSERT INTO tbl_customer (AccountNo,Title,FirstName,LastName,BusinessName,DisplayName,Email,Mobile,Phone,Website,Address,City,County,PostalCode,Country,
                SameAsBilling,BillingAddress,BillingCity,BillingCounty,BillingCountry,BillingPostalCode,FullBillingAddress,NTN,SalesTaxNumber,CNIC,IsFiler,IsAdvanceTax,AdvanceTax,
                Notes,`SalesTaxWithheldAtSource`,`ExtraTax`,`FurtherTax`,`FedPayable`,`Status`,CreatedBy,CreatedOn)
            SELECT 	AccountNo,Title,FirstName,LastName,BusinessName,DisplayName,Email,Mobile,Phone,Website,Address,City,County,PostalCode,Country,
                SameAsBilling,BillingAddress,BillingCity,BillingCounty,BillingCountry,BillingPostalCode,FullBillingAddress,NTN,SalesTaxNumber,CNIC,IsFiler,IsAdvanceTax,AdvanceTax,
                Notes,`SalesTaxWithheldAtSource`,`ExtraTax`,`FurtherTax`,`FedPayable`,`Status`,CreatedBy,CreatedOn
            FROM 	temp_customer
            WHERE RequestID = ?
        ", [$requestID]);

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

        DB::statement("
            UPDATE tbl_customer uu
            JOIN (
                SELECT TID,AccountNo,Title,FirstName,LastName,BusinessName,DisplayName,Email,Mobile,Phone,Website,Address,City,County,PostalCode,Country,SameAsBilling,
			BillingAddress,BillingCity,BillingCounty,BillingCountry,BillingPostalCode,FullBillingAddress,
			NTN,SalesTaxNumber,CNIC,IsFiler,IsAdvanceTax,AdvanceTax,Notes,`SalesTaxWithheldAtSource`,`ExtraTax`,`FurtherTax`,`FedPayable`,
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
                uu.NTN = u.NTN,
                uu.CNIC = u.CNIC,
                uu.SalesTaxNumber = u.SalesTaxNumber,
                uu.IsAdvanceTax = u.IsAdvanceTax,
                uu.AdvanceTax = u.AdvanceTax,
                uu.SalesTaxWithheldAtSource = u.SalesTaxWithheldAtSource,
                uu.ExtraTax = u.ExtraTax,
                uu.FurtherTax = u.FurtherTax,
                uu.FedPayable = u.FedPayable,
                uu.Status = u.Status,
                uu.UpdatedBy = u.UpdatedBy,
                uu.UpdatedOn = u.UpdatedOn
        ", [$requestID]);

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
