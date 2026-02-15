<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'temp_customer';
    protected $primaryKey = 'ID';
    
    // Disable standard timestamps since we use CreatedOn/UpdatedOn
    public $timestamps = false;

    // Define custom timestamp columns
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

       public static function save_transaction($validated)
    {
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

     public function delete_transaction($validated = [])
    {
         $reqId = $validated['RequestID'] ?? ($this->RequestID ?? '');
         
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
     * Scope a query to only include active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('IsArchive', 0);
    }

    /**
     * Scope a query to include archived customers (or specifically find them).
     * Since we don't use a global scope, this might just be semantic, 
     * but for the restore function which calls `withArchived`, we can make it allow searching.
     */
    public function scopeWithArchived($query)
    {
        return $query; // or $query->orWhere('IsArchive', 1);
    }
    
    // Accessor for full name if needed, though DisplayName is used.
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
