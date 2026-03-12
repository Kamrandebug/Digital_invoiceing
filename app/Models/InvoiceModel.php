<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
}