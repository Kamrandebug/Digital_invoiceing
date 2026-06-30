<?php

namespace App\Models;

use App\Services\PurchaseBreakupService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseModel extends Model
{
    protected $table = 'tbl_temp_purchase_detail';

    public $timestamps = false;

    public static function Load_Supplier(): string
    {
        return "SELECT ID, DisplayName, FirstName, LastName, BusinessName,
                       IsFiler, SalesTaxWithheldAtSource, ExtraTax, FurtherTax, FedPayable,
                       IsAdvanceTax, AdvanceTax
                FROM tbl_customer
                WHERE Status = 1
                  AND PartyType IN (2, 3)
                ORDER BY BusinessName, DisplayName";
    }

    public static function Load_Product(): string
    {
        return "SELECT p.ID, p.Name AS ProductName, p.Cost, p.CurrentQuantity, p.UomID,
                       0 AS GstPercentage,
                       p.HsCode, p.SaleInformation, p.UomID,
                       (SELECT u.UnitName FROM tbl_Unit u WHERE u.id = p.UomID LIMIT 1) AS UomName,
                       p.SaleTypeID, p.SaleTypeToRateID, p.SroScheduleID, p.SroItemID, p.TPRate, p.IsFixedRate
                FROM tbl_product p
                WHERE p.Status = 1
                ORDER BY p.Name";
    }

    public static function sqlSupplierDisplayName(): \Illuminate\Database\Query\Expression
    {
        return DB::raw(
            'COALESCE(NULLIF(TRIM(sup.BusinessName),\'\'), NULLIF(TRIM(sup.DisplayName),\'\'), '
            . 'TRIM(CONCAT(COALESCE(sup.FirstName,\'\'),\' \',COALESCE(sup.LastName,\'\')))) AS SupplierDisplayName'
        );
    }

    public static function getlistingdata(array $filters = [])
    {
        $latestBreakupSub = DB::table('purchase_breakup_jobs as bj_inner')
            ->select('bj_inner.purchase_id', DB::raw('MAX(bj_inner.version_no) as max_version'))
            ->groupBy('bj_inner.purchase_id');

        $lineCountSub = DB::table('tbl_purchase_detail')
            ->select('PurchaseID', DB::raw('COUNT(*) as PurchaseLineCount'))
            ->where('Status', '!=', 2)
            ->groupBy('PurchaseID');

        $query = DB::table('tbl_purchase_header as h')
            ->leftJoin('tbl_customer as sup', 'h.SupplierID', '=', 'sup.ID')
            ->leftJoinSub($lineCountSub, 'plc', function ($join) {
                $join->on('plc.PurchaseID', '=', 'h.ID');
            })
            ->leftJoinSub($latestBreakupSub, 'bj_max', function ($join) {
                $join->on('bj_max.purchase_id', '=', 'h.ID');
            })
            ->leftJoin('purchase_breakup_jobs as bj', function ($join) {
                $join->on('bj.purchase_id', '=', 'h.ID')
                    ->on('bj.version_no', '=', 'bj_max.max_version');
            })
            ->select(
                'h.ID as HeaderID',
                'h.DocNo',
                'h.PurchaseDate',
                'h.Status',
                'h.SupplierID',
                'h.Notes',
                'h.SubTotal',
                'h.ValueSalesExcludingST',
                'h.SalesTaxApplicable',
                DB::raw('COALESCE(h.SalesTaxApplicable, 0) AS TotalGST'),
                'h.Discount',
                'h.TotalAmount',
                'h.CreatedOn',
                self::sqlSupplierDisplayName(),
                DB::raw('COALESCE(plc.PurchaseLineCount, 0) as PurchaseLineCount'),
                'bj.id as BreakupJobId',
                'bj.breakup_status as BreakupStatus',
                'bj.version_no as BreakupVersion',
                'bj.total_rows as BreakupTotalRows'
            )
            ->where('h.Status', '!=', 2);

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('h.DocNo', 'LIKE', "%{$s}%")
                    ->orWhere('sup.BusinessName', 'LIKE', "%{$s}%")
                    ->orWhereRaw(
                        'COALESCE(NULLIF(TRIM(sup.DisplayName),\'\'), '
                        . 'TRIM(CONCAT(COALESCE(sup.FirstName,\'\'),\' \',COALESCE(sup.LastName,\'\')))) LIKE ?',
                        ["%{$s}%"]
                    );
            });
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('h.PurchaseDate', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('h.PurchaseDate', '<=', $filters['end_date']);
        }

        return $query->orderByDesc('h.CreatedOn');
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

        return '`'.str_replace('`', '``', $tenantDb).'`';
    }

    /**
     * @return array{status: bool, message: string}|null
     */
    private static function runTempPurchaseValidation(string $requestID, string $tenantDbQuoted): ?array
    {
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_temp_purchase_detail` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.DocNo), '') = '' THEN 'Invalid Document Number'
                        WHEN t.SupplierID IS NULL OR t.SupplierID <= 0 THEN 'Invalid Supplier'
                        WHEN NOT EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_customer` c
                            WHERE c.ID = t.SupplierID AND c.Status = 1
                              AND c.PartyType IN (2, 3)
                        ) THEN 'Invalid Supplier'
                        WHEN t.ProductID IS NULL OR t.ProductID <= 0 THEN 'Invalid Product'
                        WHEN NOT EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_product` p
                            WHERE p.ID = t.ProductID AND p.Status = 1
                        ) THEN 'Invalid Product'
                        WHEN t.Quantity IS NULL OR t.Quantity <= 0 THEN 'Invalid Quantity'
                        WHEN t.Rate IS NULL OR t.Rate < 0 THEN 'Invalid Rate'
                        ELSE 'Pass'
                    END AS ErrMsg
                FROM {$tenantDbQuoted}.`tbl_temp_purchase_detail` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrMsg = u.ErrMsg
        ", [$requestID]);

        $errorSummary = DB::connection('landlord')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrMsg ORDER BY t.ErrMsg SEPARATOR ', ') AS ErrMsg
            FROM {$tenantDbQuoted}.`tbl_temp_purchase_detail` t
            WHERE t.ErrMsg IS NOT NULL
              AND t.ErrMsg <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrMsg ?: 'Unable to save purchase due to invalid data.',
            ];
        }

        return null;
    }

    private static function persistPurchaseFromTemp(string $requestID, string $tenantDbQuoted): void
    {
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.tbl_temp_purchase_detail uu
            JOIN (
                SELECT t.ID, HsCode, fho.`description` AS Uom_Desc, fttc.`transactioN_DESC` AS SaleTypeDesc,
                        fsttr.`ratE_DESC` AS SaleTypeToRateDesc, fss.`srO_DESC` AS SroScheduleDesc,
                        fsi.`srO_ITEM_DESC` AS SroItemSerialNo, ProductDescription,
                        t.Amount AS `ValueSalesExcludingST`,
                        IFNULL(t.TPRate, 0.00) * IFNULL(t.Quantity, 0.00) AS `FixedNotifiedValueOrRetailPrice`,
                        t.GSTAmount AS `SalesTaxApplicable`, t.`TotalValues`, t.RequestID
                FROM {$tenantDbQuoted}.`tbl_temp_purchase_detail` t
                LEFT JOIN `core_digital_invoicing`.`fbr_hscode_uom` fho ON (fho.`uoM_ID` = t.UomID AND BINARY fho.`hs_CODE` = BINARY t.HsCode)
                LEFT JOIN `core_digital_invoicing`.`fbr_trans_type_code` fttc ON (fttc.`transactioN_TYPE_ID` = t.SaleTypeID)
                LEFT JOIN `core_digital_invoicing`.`fbr_sale_type_to_rate` fsttr ON (fsttr.`ratE_ID` = t.SaleTypeToRateID AND fsttr.`transactioN_TYPE_ID` = fttc.`transactioN_TYPE_ID`)
                LEFT JOIN `core_digital_invoicing`.`fbr_sro_schedule` fss ON (fss.`srO_ID` = t.SroScheduleID)
                LEFT JOIN `core_digital_invoicing`.`fbr_sro_item` fsi ON (fsi.`srO_ITEM_ID` = t.SroItemID)
                WHERE t.RequestID = ?
            ) u ON uu.ID = u.ID
            SET
                uu.SaleTypeDesc = u.SaleTypeDesc,
                uu.SaleTypeToRateDesc = u.SaleTypeToRateDesc,
                uu.SroScheduleDesc = u.SroScheduleDesc,
                uu.SroItemSerialNo = u.SroItemSerialNo,
                uu.`ValueSalesExcludingST` = u.`ValueSalesExcludingST`,
                uu.`FixedNotifiedValueOrRetailPrice` = u.`FixedNotifiedValueOrRetailPrice`,
                uu.`SalesTaxApplicable` = u.`SalesTaxApplicable`,
                uu.`TotalValues` = u.`TotalValues`
        ", [$requestID]);

        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.tbl_purchase_header (
                ID, PurchaseID, DocNo, PurchaseDate, SupplierID, Notes,
                SubTotal, ValueSalesExcludingST, SalesTaxApplicable, Discount,
                ExtraTax, FurtherTax, FedPayable, SalesTaxWithheldAtSource,
                TotalAmount, Status, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn
            )
            SELECT
                t.TID AS ID,
                t.TID AS PurchaseID,
                t.DocNo,
                t.PurchaseDate,
                t.SupplierID,
                MAX(t.Notes) AS Notes,
                SUM(COALESCE(t.Amount, 0)) AS SubTotal,
                SUM(COALESCE(t.ValueSalesExcludingST, t.Amount, 0)) AS ValueSalesExcludingST,
                SUM(COALESCE(t.SalesTaxApplicable, t.GSTAmount, 0)) AS SalesTaxApplicable,
                SUM(COALESCE(t.DiscountAmount, 0)) AS Discount,
                SUM(COALESCE(t.ExtraTax, 0)) AS ExtraTax,
                SUM(COALESCE(t.FurtherTax, 0)) AS FurtherTax,
                SUM(COALESCE(t.FedPayable, 0)) AS FedPayable,
                MAX(COALESCE(t.SalesTaxWithheldAtSource, 0)) AS SalesTaxWithheldAtSource,
                SUM(COALESCE(t.TotalValues, t.Amount, 0)) AS TotalAmount,
                MAX(t.Status) AS Status,
                t.CreatedBy,
                t.CreatedOn,
                t.UpdatedBy,
                t.UpdatedOn
            FROM {$tenantDbQuoted}.tbl_temp_purchase_detail t
            WHERE t.RequestID = ?
            GROUP BY t.TID, t.DocNo, t.PurchaseDate, t.SupplierID,
                     t.CreatedBy, t.CreatedOn, t.UpdatedBy, t.UpdatedOn
        ", [$requestID]);

        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.tbl_purchase_detail (
                PurchaseID, ProductID, Quantity, Rate, Amount, UomID, ProductDescription, Notes,
                HsCode, SaleTypeID, SaleTypeDesc, SaleTypeToRateID, SaleTypeToRateDesc,
                SroScheduleID, SroScheduleDesc, SroItemID, SroItemSerialNo, RateType, TPRate,
                DiscountPercentage, DiscountAmount, GstPercentage, GSTAmount,
                ValueSalesExcludingST, FixedNotifiedValueOrRetailPrice, SalesTaxApplicable,
                SalesTaxWithheldAtSource, ExtraTax, FurtherTax, FedPayable, TotalValues,
                Status, CreatedBy, CreatedOn, UpdatedBy, UpdatedOn
            )
            SELECT
                t.PurchaseID,
                t.ProductID,
                t.Quantity,
                t.Rate,
                COALESCE(t.Amount, t.Quantity * t.Rate) AS Amount,
                t.UomID,
                t.ProductDescription,
                t.Notes,
                t.HsCode,
                t.SaleTypeID,
                t.SaleTypeDesc,
                t.SaleTypeToRateID,
                t.SaleTypeToRateDesc,
                t.SroScheduleID,
                t.SroScheduleDesc,
                t.SroItemID,
                t.SroItemSerialNo,
                t.RateType,
                t.TPRate,
                t.DiscountPercentage,
                t.DiscountAmount,
                t.GstPercentage,
                t.GSTAmount,
                COALESCE(t.ValueSalesExcludingST, t.Amount, 0) AS ValueSalesExcludingST,
                COALESCE(t.FixedNotifiedValueOrRetailPrice, IFNULL(t.TPRate, 0) * IFNULL(t.Quantity, 0)) AS FixedNotifiedValueOrRetailPrice,
                COALESCE(t.SalesTaxApplicable, t.GSTAmount, 0) AS SalesTaxApplicable,
                COALESCE(t.SalesTaxWithheldAtSource, 0) AS SalesTaxWithheldAtSource,
                COALESCE(t.ExtraTax, 0) AS ExtraTax,
                COALESCE(t.FurtherTax, 0) AS FurtherTax,
                COALESCE(t.FedPayable, 0) AS FedPayable,
                COALESCE(t.TotalValues, t.Amount, 0) AS TotalValues,
                1 AS Status,
                t.CreatedBy,
                t.CreatedOn,
                t.UpdatedBy,
                t.UpdatedOn
            FROM {$tenantDbQuoted}.tbl_temp_purchase_detail t
            WHERE t.RequestID = ?
        ", [$requestID]);
    }

    /**
     * @return array{status: bool, message: string}|null
     */
    private static function runTempPurchaseImportValidation(string $requestID, string $tenantDbQuoted): ?array
    {
        // TODO: Add validation SQL against tbl_temp_purchase_importtable for RequestID = ?
        return null;
    }

    private static function persistPurchaseImportFromTemp(string $requestID, string $tenantDbQuoted): void
    {
        // TODO: Add persist SQL from tbl_temp_purchase_importtable into purchase tables for RequestID = ?
    }

    /**
     * @return array{status: bool, message: ?string}
     */
    public static function save_import_data(string $requestID): array
    {
        $requestID = trim($requestID);
        if ($requestID === '') {
            throw new \RuntimeException('RequestID is required for save_import_data.');
        }

        $tenantDbQuoted = self::tenantDatabaseQuoted();
        $validationError = self::runTempPurchaseImportValidation($requestID, $tenantDbQuoted);
        if ($validationError !== null) {
            return $validationError;
        }

        DB::transaction(function () use ($requestID, $tenantDbQuoted) {
            self::persistPurchaseImportFromTemp($requestID, $tenantDbQuoted);
        });

        return ['status' => true, 'message' => null];
    }

    /**
     * @return array{status: bool, message: ?string}
     */
    public static function save_transaction(string $requestID): array
    {
        $requestID = trim($requestID);
        if ($requestID === '') {
            throw new \RuntimeException('RequestID is required for save_transaction.');
        }

        $tenantDbQuoted = self::tenantDatabaseQuoted();
        $validationError = self::runTempPurchaseValidation($requestID, $tenantDbQuoted);
        if ($validationError !== null) {
            return $validationError;
        }

        $stock = new StockService;

        DB::transaction(function () use ($requestID, $tenantDbQuoted, $stock) {
            self::persistPurchaseFromTemp($requestID, $tenantDbQuoted);

            $lines = DB::table('tbl_temp_purchase_detail')
                ->where('RequestID', $requestID)
                ->get(['ProductID', 'Quantity', 'Rate']);

            $stock->applyPurchaseLines($lines);
        });

        return ['status' => true, 'message' => null];
    }

    /**
     * @return array{status: bool, message: ?string}
     */
    public static function edit_transaction(string $requestID): array
    {
        $requestID = trim($requestID);
        if ($requestID === '') {
            throw new \RuntimeException('RequestID is required for edit_transaction.');
        }

        $tenantDbQuoted = self::tenantDatabaseQuoted();
        $validationError = self::runTempPurchaseValidation($requestID, $tenantDbQuoted);
        if ($validationError !== null) {
            return $validationError;
        }

        $tid = DB::table('tbl_temp_purchase_detail')
            ->where('RequestID', $requestID)
            ->value('TID');

        if (! $tid) {
            return ['status' => false, 'message' => 'Purchase reference missing from staging data.'];
        }

        $oldLines = DB::table('tbl_purchase_detail')
            ->where('PurchaseID', $tid)
            ->where('Status', '!=', 2)
            ->get(['ProductID', 'Quantity', 'Rate', 'TotalValues']);

        $stock = new StockService;

        DB::transaction(function () use ($requestID, $tenantDbQuoted, $tid, $oldLines, $stock) {
            $stock->reversePurchaseLines($oldLines);

            DB::connection('landlord')->statement("
                DELETE FROM {$tenantDbQuoted}.tbl_purchase_detail
                WHERE PurchaseID = ?
            ", [$tid]);

            DB::connection('landlord')->statement("
                DELETE FROM {$tenantDbQuoted}.tbl_purchase_header
                WHERE ID = ?
            ", [$tid]);

            self::persistPurchaseFromTemp($requestID, $tenantDbQuoted);

            $newLines = DB::table('tbl_temp_purchase_detail')
                ->where('RequestID', $requestID)
                ->get(['ProductID', 'Quantity', 'Rate', 'TotalValues']);

            $stock->applyPurchaseLines($newLines);
        });

        $newLines = DB::table('tbl_purchase_detail')
            ->where('PurchaseID', $tid)
            ->where('Status', '!=', 2)
            ->get(['ProductID', 'Quantity', 'Rate', 'TotalValues']);

        app(PurchaseBreakupService::class)->expireIfMaterialChange((int) $tid, $oldLines, $newLines);

        return ['status' => true, 'message' => null];
    }

    public static function delete_transaction(string $requestID): void
    {
        $requestID = trim($requestID);
        if ($requestID === '') {
            return;
        }

        $tid = DB::table('tbl_temp_purchase_detail')
            ->where('RequestID', $requestID)
            ->value('TID');

        if (! $tid) {
            DB::statement("
                UPDATE tbl_purchase_header uu
                JOIN (
                    SELECT TID, 2 AS Status, UpdatedBy, UpdatedOn
                    FROM tbl_temp_purchase_detail
                    WHERE RequestID = ?
                ) u ON uu.ID = u.TID
                SET uu.Status = u.Status, uu.UpdatedBy = u.UpdatedBy, uu.UpdatedOn = u.UpdatedOn
            ", [$requestID]);

            return;
        }

        $oldLines = DB::table('tbl_purchase_detail')
            ->where('PurchaseID', $tid)
            ->where('Status', '!=', 2)
            ->get(['ProductID', 'Quantity', 'Rate', 'TotalValues']);

        $stock = new StockService;

        DB::transaction(function () use ($requestID, $tid, $oldLines, $stock) {
            $stock->reversePurchaseLines($oldLines);

            DB::table('tbl_purchase_detail')
                ->where('PurchaseID', $tid)
                ->update(['Status' => 2]);

            DB::statement("
                UPDATE tbl_purchase_header uu
                JOIN (
                    SELECT TID, 2 AS Status, UpdatedBy, UpdatedOn
                    FROM tbl_temp_purchase_detail
                    WHERE RequestID = ?
                ) u ON uu.ID = u.TID
                SET uu.Status = u.Status, uu.UpdatedBy = u.UpdatedBy, uu.UpdatedOn = u.UpdatedOn
            ", [$requestID]);
        });
    }
}
