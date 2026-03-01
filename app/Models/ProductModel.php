<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductModel extends Model
{
    use HasFactory;

    protected $table = 'temp_product';
    protected $primaryKey = 'ID';

    // Custom timestamps
    public $timestamps = false;

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'TID',
        'VoucherID',
        'ProductID',
        'Type',
        'Name',
        'Image',
        'Code',
        'CategoryName',
        'CategoryID',
        'ParentCategoryID',
        'ParentLevel1',
        'ParentLevel2',
        'ParentLevel3',
        'SaleInformation',
        'SalePrice',
        'TaxRegion',
        'TaxCode',
        'TaxRate',
        'NFTaxRate',
        'NFTaxCode',
        'IncomeAccount',
        'LowStockLevel',
        'CurrentQuantity',
        'Date',
        'StockAccount',
        'ExpenseAccount',
        'IsSelltoCustomer',
        'Status',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn',
        'StockStatus',
        'Cost',
        'OpeningQuantity',
        'purchaseStatus',
        'OpeningRate',
        'OptionalField1',
        'OptionalField2',
        'OptionalField3',
        'IsShipping',
        'IsPurchasing',
        'TPRate',
        'TPMargin',
        'Unit',
        'UnitName',
        'UMUnitID',
        'UMUnitName',
        'FactorRate',
        'TrialRate',
        'Discount',
        'Weight',
        'PurchaseDiscount',
        'TOSale',
        'TOPurchase',
        'RMSale',
        'RMPurchase',
        'TPSale',
        'TPPurchase',
        'PreferredSupplierAccount',
        'PreferredSupplier',
        'TaxSchID',
        'IsRawMaterial',
        'BatchNo',
        'BatchExpiryDate',
        'ToLocationID',
        'Location',
        'ErrMsg',
        'ExcelRow',
        'Flag',
        'RequestID',
    ];

    public static function getlistingdata($filters = [])
{
    $query = "SELECT * FROM crm_product WHERE Status = 1";
    $bindings = [];

    // Search filter
    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $query .= " AND (Name LIKE ? OR Code LIKE ? OR SaleInformation LIKE ?)";
        $bindings = array_merge($bindings, [$search, $search, $search]);
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
    /**
     * Execute save: insert from temp_product into crm_product. No return; runs query inside.
     */
    public static function save_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        DB::unprepared("
            INSERT INTO crm_product (VoucherID,TYPE,NAME,Image,CODE,Category,SaleInformation,SalePrice,TaxCode,TaxRate,NFTaxRate,IncomeAccount,LowStockLevel,CurrentQuantity,DATE,StockAccount,ExpenseAccount,IsSelltoCustomer,STATUS,CreatedBy,CreatedOn,UpdatedBy,UpdatedOn,RequestID,StockStatus,Cost,OpeningQuantity,purchaseStatus,OpeningRate,OptionalField1,OptionalField2,OptionalField3,IsShipping,IsPurchasing,TPRate,TPMargin,Unit,TrialRate,Discount,Weight,PurchaseDiscount,TOSale,TOPurchase,RMSale,RMPurchase,TPSale,TPPurchase,PreferredSupplier,TaxSchID,IsRawMaterial)
            SELECT VoucherID,TYPE,NAME,Image,CODE,1 AS CategoryID,SaleInformation,SalePrice,TaxCode,TaxRate,NFTaxRate,IncomeAccount,LowStockLevel,CurrentQuantity,DATE,StockAccount,ExpenseAccount,IsSelltoCustomer,STATUS,CreatedBy,CreatedOn,UpdatedBy,UpdatedOn,RequestID,StockStatus,Cost,OpeningQuantity,purchaseStatus,OpeningRate,OptionalField1,OptionalField2,OptionalField3,IsShipping,IsPurchasing,TPRate,TPMargin,Unit,TrialRate,Discount,Weight,PurchaseDiscount,TOSale,TOPurchase,RMSale,RMPurchase,TPSale,TPPurchase,PreferredSupplier,TaxSchID,IsRawMaterial
            FROM temp_product
            WHERE RequestID = '$requestID'
        ");
    }

    /**
     * Execute edit: update crm_product from temp_product. No return; runs query inside.
     */
    public static function edit_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        DB::unprepared("
            UPDATE crm_product uu
            JOIN (
                SELECT Code, Name, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn, TID
                FROM temp_product
                WHERE RequestID = '$requestID'
            ) u ON uu.ID = u.TID
            SET
                uu.Code = u.Code,
                uu.Name = u.Name,
                uu.UpdatedBy = u.UpdatedBy,
                uu.UpdatedOn = u.UpdatedOn
        ");
    }

    /**
     * Execute delete: soft-update crm_product status from temp_product. No return; runs query inside.
     */
    public static function delete_transaction($validated = [])
    {
        $requestID = $validated['RequestID'] ?? '';
        DB::unprepared("
            UPDATE crm_product uu
            JOIN (
                SELECT TID, 2 AS Status, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn
                FROM temp_product
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
        return $query->where('Status', 1);
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
        return $this->Name ?? $this->Code;
    }

    protected $casts = [
        'CreatedOn'        => 'datetime',
        'UpdatedOn'        => 'datetime',
        'Date'             => 'date',
        'BatchExpiryDate'  => 'date',
        'SalePrice'        => 'decimal:6',
        'Cost'             => 'decimal:6',
        'LowStockLevel'    => 'decimal:6',
        'CurrentQuantity'  => 'decimal:6',
        'OpeningQuantity'  => 'decimal:6',
        'OpeningRate'      => 'decimal:6',
        'FactorRate'       => 'decimal:10',
        'TPRate'           => 'decimal:6',
        'TPMargin'         => 'decimal:6',
        'TrialRate'        => 'decimal:6',
        'Discount'         => 'decimal:4',
        'Weight'           => 'decimal:6',
        'PurchaseDiscount' => 'decimal:4',
        'TOSale'           => 'decimal:6',
        'TOPurchase'       => 'decimal:6',
        'RMSale'           => 'decimal:6',
        'RMPurchase'       => 'decimal:6',
        'TPSale'           => 'decimal:6',
        'TPPurchase'       => 'decimal:6',
        'IsSelltoCustomer' => 'boolean',
        'IsShipping'       => 'boolean',
        'IsPurchasing'     => 'boolean',
        'IsRawMaterial'    => 'boolean',
        'purchaseStatus'   => 'boolean',
        'Flag'             => 'boolean',
        'Status'           => 'integer',
        'StockStatus'      => 'integer',
    ];
}