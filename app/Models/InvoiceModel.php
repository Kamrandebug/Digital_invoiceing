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
        $query = DB::select("
                SELECT
                    ID,
                    COALESCE(NULLIF(TRIM(DisplayName),''),
                        CONCAT(COALESCE(TRIM(FirstName),''), ' ', COALESCE(TRIM(LastName),''))) AS DisplayName,
                    Address
                FROM crm_customer
                WHERE Status != 2
                AND (IsArchive IS NULL OR IsArchive = 0)
                ORDER BY DisplayName ASC
");
        return $query;
    }

    public static function Load_Product()
    {
       $query = DB::select("
        SELECT
            ID,
            Name     AS ProductName,
            SalePrice,
            TaxRate  AS GstPercentage
        FROM crm_product
        WHERE Status != 2
        ORDER BY Name ASC
");

        return $query;
    }

   // ══════════════════════════════════════════════════════════
    //  LISTING
    // ══════════════════════════════════════════════════════════

    public static function getlistingdata($filters =[])
    {
        // 1. Point directly to tbl_sales_invoice_header (aliased as 'h')
        $query = DB::table('tbl_sales_invoice_header as h')
            ->leftJoin('crm_customer as cust', 'h.CustomerID', '=', 'cust.ID')
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

    public static function save_transaction($validated)
    {
        $query = "";

        return $query;
    }

    public static function edit_transaction($validated)
    {
        $query = "";

        return $query;
    }

    public static function delete_transaction($validated = [])
    {
        $query = "";

        return $query;
    }
}