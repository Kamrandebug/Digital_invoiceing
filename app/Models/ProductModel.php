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

    // Tenant catalog lives on the session-scoped tenant connection (not landlord).
    return DB::connection('tenant')->select($query, $bindings);
}

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
     * Execute save: insert from temp_product into tbl_product.
     * Returns status and error message when temp rows fail validation (same pattern as Customer).
     */
    public static function save_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        $tenantDbQuoted = self::tenantDatabaseQuoted();

        // 1. Unified ID Resolution Query (Strict Lookup — No Forced Defaults)
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`temp_product` t
            LEFT JOIN `core_digital_invoicing`.`fbr_hscode_uom` fhu 
              ON (
                  fhu.`hs_CODE` = TRIM(t.`HsCode`) 
                  AND (fhu.`description` = TRIM(t.`Uom_Desc`) OR fhu.`uoM_ID` = t.`UOM`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_trans_type_code` fttc 
              ON (
                  fttc.`transactioN_DESC` = TRIM(t.`SaleTypeDesc`) 
                  OR fttc.`transactioN_TYPE_ID` = t.`SaleType`
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr 
              ON (
                  fsttr.`transactioN_TYPE_ID` = COALESCE(fttc.`transactioN_TYPE_ID`, t.`SaleType`)
                  AND (fsttr.`ratE_DESC` = TRIM(t.`SaleTypeToRateDesc`) OR fsttr.`ratE_ID` = t.`SaleTypeToRate`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss 
              ON (
                  fss.`ratE_ID` = COALESCE(fsttr.`ratE_ID`, t.`SaleTypeToRate`)
                  AND (fss.`srO_DESC` = TRIM(t.`SroScheduleDesc`) OR fss.`srO_ID` = t.`SroSchedule`)
              )
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi 
              ON (
                  fsi.`srO_ID` = COALESCE(fss.`srO_ID`, t.`SroSchedule`)
                  AND (fsi.`srO_ITEM_DESC` = TRIM(t.`SroItemSerialNo`) OR fsi.`srO_ITEM_ID` = t.`SroItem`)
              )
            SET 
                t.`SaleInformation` = COALESCE(NULLIF(TRIM(t.`SaleInformation`), ''), t.`Name`),
                t.`UOM`             = fhu.`uoM_ID`,
                t.`SaleType`        = fttc.`transactioN_TYPE_ID`,
                t.`SaleTypeToRate`  = fsttr.`ratE_ID`,
                t.`SroSchedule`     = fss.`srO_ID`,
                t.`SroItem`         = fsi.`srO_ITEM_ID`
            WHERE t.`RequestID` = ?
        ", [$requestID]);

        // 2. Validation Query (Catches Invalid/Unmatched Lookups and missing required data)
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`temp_product` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        -- Basic required fields
                        WHEN COALESCE(TRIM(t.`Name`), '') = '' THEN 'Invalid Product Name'
                        WHEN COALESCE(TRIM(t.`Code`), '') = '' THEN 'Invalid Product Code'
                        
                        -- Batch & Database Duplicates
                        WHEN (
                            SELECT COUNT(*) FROM {$tenantDbQuoted}.`temp_product` tp 
                            WHERE tp.RequestID = t.RequestID 
                              AND LOWER(TRIM(tp.Code)) = LOWER(TRIM(t.Code))
                        ) > 1 THEN CONCAT('Product Code \'', TRIM(t.Code), '\' duplicated in batch')
                        WHEN EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_product` p
                            WHERE LOWER(TRIM(p.`Code`)) = LOWER(TRIM(t.`Code`)) 
                              AND (p.`Status` <> 2 OR p.`Status` IS NULL)
                        ) THEN CONCAT('Product Code \'', TRIM(t.Code), '\' already exists')

                        -- FBR Lookup Validations (Reports exact invalid text if provided but unresolved)
                        WHEN COALESCE(TRIM(t.`HsCode`), '') <> '' 
                          AND NOT EXISTS (
                              SELECT 1 FROM `core_digital_invoicing`.`fbr_hscode_uom` h 
                              WHERE h.`hs_CODE` = TRIM(t.`HsCode`)
                          ) THEN CONCAT('Invalid HS Code \'', TRIM(t.HsCode), '\'')

                        WHEN COALESCE(TRIM(t.`Uom_Desc`), '') <> '' AND t.`UOM` IS NULL 
                            THEN CONCAT('Invalid UOM \'', TRIM(t.Uom_Desc), '\' for HS Code \'', COALESCE(TRIM(t.HsCode), 'N/A'), '\'')
                        WHEN COALESCE(TRIM(t.`SaleTypeDesc`), '') <> '' AND t.`SaleType` IS NULL 
                            THEN CONCAT('Invalid Sale Type \'', TRIM(t.SaleTypeDesc), '\'')
                        WHEN COALESCE(TRIM(t.`SaleTypeToRateDesc`), '') <> '' AND t.`SaleTypeToRate` IS NULL 
                            THEN CONCAT('Invalid Sale Type Rate \'', TRIM(t.SaleTypeToRateDesc), '\'')
                        WHEN COALESCE(TRIM(t.`SroScheduleDesc`), '') <> '' AND t.`SroSchedule` IS NULL 
                            THEN CONCAT('Invalid SRO Schedule \'', TRIM(t.SroScheduleDesc), '\'')
                        WHEN COALESCE(TRIM(t.`SroItemSerialNo`), '') <> '' AND t.`SroItem` IS NULL 
                            THEN CONCAT('Invalid SRO Item \'', TRIM(t.SroItemSerialNo), '\'')

                        ELSE 'Pass'
                    END AS ErrMsg
                FROM {$tenantDbQuoted}.`temp_product` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrMsg = u.ErrMsg
        ", [$requestID]);

        $failedRows = DB::connection('tenant')->table('temp_product')
            ->where('RequestID', $requestID)
            ->whereNotNull('ErrMsg')
            ->where('ErrMsg', '<>', 'Pass')
            ->orderBy('ExcelRow', 'asc')
            ->orderBy('ID', 'asc')
            ->get();

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

        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.tbl_product (
                VoucherID,`Name`,`Code`,`Type`,CategoryID,CurrentQuantity,Unit,SalePrice,Cost,TPRate,IsFixedRate,Weight,Image,
                HsCode,UomID,Uom_Desc,SaleTypeID,SaleTypeDesc,SaleTypeToRateID,SaleTypeToRateDesc,SroScheduleID,SroScheduleDesc,
                SroItemID,SroItemSerialNo,SaleInformation,`Status`,CreatedBy,CreatedOn,RequestID
            )
            SELECT
                VoucherID,`Name`,`Code`,`Type`,t.CategoryID,CurrentQuantity,t.Unit,SalePrice,Cost,TPRate,IsFixedRate,Weight,Image,
                HsCode,UOM,COALESCE(t.Uom_Desc, fho.`description`),SaleType,COALESCE(t.SaleTypeDesc, fttc.`transactioN_DESC`),
                SaleTypeToRate,COALESCE(t.SaleTypeToRateDesc, fsttr.`ratE_DESC`),SroSchedule,COALESCE(t.SroScheduleDesc, fss.`srO_DESC`),
                SroItem,COALESCE(t.SroItemSerialNo, fsi.`srO_ITEM_DESC`),SaleInformation,`Status`,CreatedBy,CreatedOn,RequestID
            FROM {$tenantDbQuoted}.temp_product t
            LEFT JOIN `core_digital_invoicing`.`fbr_hscode_uom` fho ON (fho.`uoM_ID` = t.UOM AND fho.hs_CODE = t.HsCode)
            LEFT JOIN `core_digital_invoicing`.`fbr_trans_type_code` fttc ON (fttc.`transactioN_TYPE_ID` = t.SaleType)
            LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr ON (fsttr.`ratE_ID` = t.SaleTypeToRate AND fsttr.`transactioN_TYPE_ID` = fttc.`transactioN_TYPE_ID`)
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss ON (fss.`srO_ID` = t.SroSchedule)
            LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi ON (fsi.`srO_ITEM_ID` = t.SroItem)
            WHERE t.RequestID = ? AND (t.ErrMsg = 'Pass' OR t.ErrMsg IS NULL)
        ", [$requestID]);

        try {
            $inserted = DB::connection('tenant')->table('tbl_product')->where('RequestID', $requestID)->get();
            foreach ($inserted as $prod) {
                \App\Services\AuditLogService::log('CREATE', 'PRODUCT', $prod->ID, null, (array)$prod);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log product creation: ' . $e->getMessage());
        }

        return [
            'status' => true,
            'message' => null,
        ];
    }

    /**
     * Execute edit: update tbl_product from temp_product.
     * Returns status and error message when temp rows fail validation (same pattern as Customer).
     */
    public static function edit_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        $tenantDbQuoted = self::tenantDatabaseQuoted();

        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`temp_product` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.`Name`), '') = '' THEN 'Invalid Product Name'
                        WHEN COALESCE(TRIM(t.`Code`), '') = '' THEN 'Invalid Product Code'
                        WHEN EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_product` p
                            WHERE p.`Code` = BINARY t.`Code`
                              AND p.`Status` <> 2
                              AND p.`ID` <> t.TID
                        ) THEN 'Product Code already exist'
                        ELSE 'Pass'
                    END AS ErrMsg
                FROM {$tenantDbQuoted}.`temp_product` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrMsg = u.ErrMsg
        ", [$requestID]);

        $errorSummary = DB::connection('landlord')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrMsg ORDER BY t.ErrMsg SEPARATOR ', ') AS ErrMsg
            FROM {$tenantDbQuoted}.`temp_product` t
            WHERE t.`ErrMsg` IS NOT NULL
              AND t.`ErrMsg` <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrMsg ?: 'Unable to update product due to invalid data.',
            ];
        }

        // Fetch old values before updating
        $oldRecord = null;
        try {
            $tempRow = DB::connection('tenant')->table('temp_product')->where('RequestID', $requestID)->first();
            if ($tempRow && !empty($tempRow->TID)) {
                $oldRecord = DB::connection('tenant')->table('tbl_product')->where('ID', $tempRow->TID)->first();
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch old product details for audit: ' . $e->getMessage());
        }

        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.tbl_product uu
            JOIN (
                SELECT t.TID, VoucherID,`Name`,`Code`,`Type`,t.CategoryID,CurrentQuantity,t.Unit,SalePrice,Cost,TPRate,IsFixedRate,Weight,Image,
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
                uu.IsFixedRate        = u.IsFixedRate,
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

        // Fetch new values after updating
        try {
            if ($oldRecord) {
                $newRecord = DB::connection('tenant')->table('tbl_product')->where('ID', $oldRecord->ID)->first();
                if ($newRecord) {
                    \App\Services\AuditLogService::log('UPDATE', 'PRODUCT', $oldRecord->ID, (array)$oldRecord, (array)$newRecord);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log product edit: ' . $e->getMessage());
        }

        return [
            'status' => true,
            'message' => null,
        ];
    }

    /**
     * Execute delete: soft-update tbl_product status from temp_product. No return; runs query inside.
     */
    public static function delete_transaction($validated = [])
    {
        $requestID = $validated['RequestID'] ?? '';

        // Fetch old values before deleting
        $oldRecord = null;
        $productId = null;
        try {
            $tempRow = DB::connection('tenant')->table('temp_product')->where('RequestID', $requestID)->first();
            if ($tempRow && !empty($tempRow->TID)) {
                $productId = $tempRow->TID;
                $oldRecord = DB::connection('tenant')->table('tbl_product')->where('ID', $productId)->first();
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch old product details for delete audit: ' . $e->getMessage());
        }

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

        try {
            if ($productId && $oldRecord) {
                \App\Services\AuditLogService::log('DELETE', 'PRODUCT', $productId, (array)$oldRecord, null);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log product deletion: ' . $e->getMessage());
        }
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