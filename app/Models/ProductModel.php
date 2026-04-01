<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductModel extends Model
{
    use HasFactory;

    protected $connection = 'landlord';
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
    $query = "SELECT * FROM tbl_product WHERE Status = 1";
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
     * Execute save: insert from temp_product into tbl_product. No return; runs query inside.
     */
    public static function save_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        $tenantDb = session('selected_tenant.dbname') ?: config('database.connections.tenant.database');
        $tenantDb = trim((string) $tenantDb);

        if ($tenantDb === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
            throw new \RuntimeException('Invalid tenant database name in session.');
        }

        $tenantDbQuoted = '`' . str_replace('`', '``', $tenantDb) . '`';

        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.tbl_product (
                VoucherID,`Name`,`Code`,`Type`,CategoryID,CurrentQuantity,Unit,SalePrice,Cost,TPRate,Weight,Image,
                HsCode,UomID,Uom_Desc,SaleTypeID,SaleTypeDesc,SaleTypeToRateID,SaleTypeToRateDesc,SroScheduleID,SroScheduleDesc,
                SroItemID,SroItemSerialNo,SaleInformation,`Status`,CreatedBy,CreatedOn,RequestID
            )
            SELECT
                VoucherID,`Name`,`Code`,`Type`,1 AS CategoryID,CurrentQuantity,Unit,SalePrice,Cost,TPRate,Weight,Image,
                HsCode,UOM,fho.`description` AS Uom_Desc,SaleType,fttc.`transactioN_DESC` AS SaleTypeDesc,
                SaleTypeToRate,fsttr.`ratE_DESC` AS SaleTypeToRateDesc,SroSchedule,fss.`srO_DESC` AS SroScheduleDesc,
                SroItem,fsi.`srO_ITEM_DESC` AS SroItemSerialNo,SaleInformation,`Status`,CreatedBy,CreatedOn,RequestID
            FROM {$tenantDbQuoted}.temp_product t
            LEFT JOIN `core_digital_invoicing`.`fbr_hscode_uom` fho ON (fho.`uoM_ID` = t.UOM AND fho.hs_CODE = t.HsCode)
            LEFT JOIN `core_digital_invoicing`.`fbr_trans_type_code` fttc ON (fttc.`transactioN_TYPE_ID` = t.SaleType)
            LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr ON (fsttr.`ratE_ID` = t.SaleTypeToRate AND fsttr.`transactioN_TYPE_ID` = fttc.`transactioN_TYPE_ID`)
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss ON (fss.`srO_ID` = t.SroSchedule)
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi ON (fsi.`srO_ITEM_ID` = t.SroItem)
            WHERE t.RequestID = ?
        ", [$requestID]);
    }

    /**
     * Execute edit: update tbl_product from temp_product. No return; runs query inside.
     */
    public static function edit_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        $tenantDb = session('selected_tenant.dbname') ?: config('database.connections.tenant.database');
        $tenantDb = trim((string) $tenantDb);

        if ($tenantDb === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
            throw new \RuntimeException('Invalid tenant database name in session.');
        }

        $tenantDbQuoted = '`' . str_replace('`', '``', $tenantDb) . '`';

        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.tbl_product uu
            JOIN (
                SELECT t.TID, VoucherID,`Name`,`Code`,`Type`,1 AS CategoryID,CurrentQuantity,Unit,SalePrice,Cost,TPRate,Weight,Image,
                HsCode,UOM,fho.`description` AS Uom_Desc,SaleType,fttc.`transactioN_DESC` AS SaleTypeDesc,
                SaleTypeToRate, fsttr.`ratE_DESC` AS SaleTypeToRateDesc, SroSchedule,fss.`srO_DESC` AS SroScheduleDesc,
                SroItem,fsi.`srO_ITEM_DESC` AS SroItemSerialNo,SaleInformation,`Status`,UpdatedBy,UpdatedOn,RequestID
            FROM {$tenantDbQuoted}.temp_product t
            LEFT JOIN `core_digital_invoicing`.`fbr_hscode_uom` fho ON (fho.`uoM_ID` = t.UOM AND fho.hs_CODE = t.HsCode)
            LEFT JOIN `core_digital_invoicing`.`fbr_trans_type_code` fttc ON (fttc.`transactioN_TYPE_ID` = t.SaleType)
            LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr ON (fsttr.`ratE_ID` = t.SaleTypeToRate AND fsttr.`transactioN_TYPE_ID` = fttc.`transactioN_TYPE_ID`)
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss ON (fss.`srO_ID` = t.SroSchedule)
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi ON (fsi.`srO_ITEM_ID` = t.SroItem)
            WHERE t.RequestID = ?
            ) u ON uu.ID = u.TID
            SET
                uu.Name               = u.Name,
                uu.Code               = u.Code,
                uu.Type               = u.Type,
                uu.CategoryID         = u.CategoryID,
                uu.CurrentQuantity    = u.CurrentQuantity,
                uu.Unit               = u.Unit,
                uu.SalePrice          = u.SalePrice,
                uu.Cost               = u.Cost,
                uu.TPRate             = u.TPRate,
                uu.Weight             = u.Weight,
                uu.Image              = u.Image,
                uu.HsCode             = u.HsCode,
                uu.UomID              = u.UOM,
                uu.Uom_Desc           = u.Uom_Desc,
                uu.SaleTypeID         = u.SaleType,
                uu.SaleTypeDesc       = u.SaleTypeDesc,
                uu.SaleTypeToRateID   = u.SaleTypeToRate,
                uu.SaleTypeToRateDesc = u.SaleTypeToRateDesc,
                uu.SroScheduleID      = u.SroSchedule,
                uu.SroScheduleDesc    = u.SroScheduleDesc,
                uu.SroItemID          = u.SroItem,
                uu.SroItemSerialNo    = u.SroItemSerialNo,
                uu.SaleInformation    = u.SaleInformation,
                uu.`Status`           = u.`Status`,
                uu.UpdatedBy          = u.UpdatedBy,
                uu.UpdatedOn          = u.UpdatedOn
        ", [$requestID]);
    }

    /**
     * Execute delete: soft-update tbl_product status from temp_product. No return; runs query inside.
     */
    public static function delete_transaction($validated = [])
    {
        $requestID = $validated['RequestID'] ?? '';
        DB::unprepared("
            UPDATE tbl_product uu
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