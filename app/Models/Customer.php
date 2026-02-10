<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'crm_customer';
    protected $primaryKey = 'ID';
    
    // Disable standard timestamps since we use CreatedOn/UpdatedOn
    public $timestamps = false;

    // Define custom timestamp columns if we wanted to use automatic handling, 
    // but the controller handles them manually. 
    // However, for consistency with Laravel, we can define them:
    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'AccountNo',
        'Title',
        'FirstName',
        'LastName',
        'BusinessName',
        'DisplayName',
        'Email',
        'Mobile',
        'Phone',
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
        'SameAsBilling',
        'NTN',
        'SalesTaxNumber',
        'IsFiler',
        'CNIC',
        'CreditLimit',
        'PaymentTerms',
        'Notes',
        'Status',
        'PartyBalance',
        'Type',
        'Typei',
        'IsDashboard',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn',
        'IsArchive',
    ];

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
