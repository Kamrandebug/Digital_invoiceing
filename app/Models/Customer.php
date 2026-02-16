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
        // Base query
        $query = "SELECT * FROM crm_customer WHERE 1=1";
        $bindings = [];

        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (DisplayName LIKE ? OR BusinessName LIKE ? OR FirstName LIKE ? OR LastName LIKE ? OR Address LIKE ? OR Mobile LIKE ? OR Phone LIKE ?)";
            $bindings = array_merge($bindings, [$search, $search, $search, $search, $search, $search, $search]);
        }
        
        // Date filter
        if (!empty($filters['start_date'])) {
            $query .= " AND DATE(CreatedOn) >= ?";
            $bindings[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND DATE(CreatedOn) <= ?";
            $bindings[] = $filters['end_date'];
        }

        // Exclude archived
        $query .= " AND Status != 2";

        // Order By
        $query .= " ORDER BY CreatedOn DESC";
        
        return DB::select($query, $bindings);
    }

    public static function save_transaction($validated)
    {
        // Insert query
        $query = "INSERT INTO crm_customer ( AccountNo, Title,FirstName, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn)
        SELECT  AccountNo, Title,FirstName, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn
        FROM temp_customer WHERE RequestID = '{$validated['RequestID']}';";

        return $query;
    }

    public static function edit_transaction($validated)
    {
        $query = "UPDATE crm_customer uu
        JOIN 
        (
        SELECT  AccountNo, Title,FirstName, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn, TID
        FROM temp_customer WHERE RequestID = '{$validated['RequestID']}'

        ) u ON uu.ID = u.TID
        SET
        uu.AccountNo = u.AccountNo,
        uu.Title = u.Title,
        uu.FirstName = u.FirstName,
        uu.UpdatedBy = u.UpdatedBy,
        uu.UpdatedOn = u.UpdatedOn";

        return $query;
    }

    public static function delete_transaction($validated = [])
    {
         $reqId = $validated['RequestID'] ?? '';
         
         $query = "UPDATE crm_customer uu 
            JOIN 
            ( 
            SELECT  TID, 2 AS Status, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn 
            FROM temp_customer WHERE RequestID = '{$reqId}'
            
            ) u ON uu.ID = u.TID 
            SET 
            uu.Status = u.Status, 
            uu.UpdatedBy = u.UpdatedBy, 
            uu.UpdatedOn = u.UpdatedOn";
  
         return $query;
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

    protected $casts = [
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
        'IsFiler' => 'boolean',
        'SameAsBilling' => 'boolean',
        'IsDashboard' => 'boolean',
        'IsArchive' => 'integer',
        'Status' => 'integer',
        'PartyBalance' => 'decimal:2',
        'CreditLimit' => 'decimal:2',
    ];
}
