<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BusinessInfo extends Model
{
    use HasFactory;

    // Temp table used for staging before applying to main table
    protected $table = 'temp_business_info';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    /**
     * Listing data from main table.
     */
    public static function getlistingdata($filters = [])
    {
        $query = DB::table('tbl_business_info')->where('IsArchive', 0);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('BusinessName', 'LIKE', "%{$search}%")
                    ->orWhere('FirstName', 'LIKE', "%{$search}%")
                    ->orWhere('LastName', 'LIKE', "%{$search}%")
                    ->orWhere('Email', 'LIKE', "%{$search}%")
                    ->orWhere('Mobile', 'LIKE', "%{$search}%")
                    ->orWhere('Phone', 'LIKE', "%{$search}%")
                    ->orWhere('Address', 'LIKE', "%{$search}%");
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
     * Save: insert from temp_business_info into tbl_business_info.
     */
    public static function save_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        DB::unprepared("
            INSERT INTO tbl_business_info (
                AccountNo, Title, FirstName, LastName, BusinessName,
                Email, Mobile, Phone, Website, Address, ImageUrl, Province,
                NTN, SalesTaxNumber, CNIC, IsFiler, Notes,
                Sandbox_access_token, iris_access_token,
                Status, IsDashboard, IsArchive,
                CreatedBy, CreatedOn, UpdatedBy, UpdatedOn
            )
            SELECT
                AccountNo, Title, FirstName, LastName, BusinessName,
                Email, Mobile, Phone, Website, Address, ImageUrl, Province,
                NTN, SalesTaxNumber, CNIC, IsFiler, Notes,
                Sandbox_access_token, iris_access_token,
                Status, IsDashboard, IsArchive,
                CreatedBy, CreatedOn, UpdatedBy, UpdatedOn
            FROM temp_business_info
            WHERE RequestID = '$requestID'
        ");

        if ($requestID !== '') {
            DB::table('temp_business_info')->where('RequestID', $requestID)->delete();
        }
    }

    /**
     * Edit: update tbl_business_info from temp_business_info.
     */
    public static function edit_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';
        DB::unprepared("
            UPDATE tbl_business_info bi
            JOIN (
                SELECT
                    TID,
                    AccountNo, Title, FirstName, LastName, BusinessName,
                    Email, Mobile, Phone, Website, Address, ImageUrl, Province,
                    NTN, SalesTaxNumber, CNIC, IsFiler, Notes,
                    Sandbox_access_token, iris_access_token,
                    Status, IsDashboard, IsArchive,
                    UpdatedBy, UpdatedOn
                FROM temp_business_info
                WHERE RequestID = '$requestID'
            ) u ON bi.ID = u.TID
            SET
                bi.AccountNo      = u.AccountNo,
                bi.Title          = u.Title,
                bi.FirstName      = u.FirstName,
                bi.LastName       = u.LastName,
                bi.BusinessName   = u.BusinessName,
                bi.Email          = u.Email,
                bi.Mobile         = u.Mobile,
                bi.Phone          = u.Phone,
                bi.Website        = u.Website,
                bi.Address        = u.Address,
                bi.ImageUrl       = u.ImageUrl,
                bi.Province       = u.Province,
                bi.NTN            = u.NTN,
                bi.SalesTaxNumber = u.SalesTaxNumber,
                bi.CNIC           = u.CNIC,
                bi.IsFiler        = u.IsFiler,
                bi.Notes          = u.Notes,
                bi.Sandbox_access_token = u.Sandbox_access_token,
                bi.iris_access_token    = u.iris_access_token,
                bi.Status         = u.Status,
                bi.IsDashboard    = u.IsDashboard,
                bi.IsArchive      = u.IsArchive,
                bi.UpdatedBy      = u.UpdatedBy,
                bi.UpdatedOn      = u.UpdatedOn
        ");

        if ($requestID !== '') {
            DB::table('temp_business_info')->where('RequestID', $requestID)->delete();
        }
    }

    /**
     * Delete: soft-archive in tbl_business_info from temp_business_info.
     */
    public static function delete_transaction($validated = [])
    {
        $requestID = $validated['RequestID'] ?? '';
        DB::unprepared("
            UPDATE tbl_business_info bi
            JOIN (
                SELECT TID, 1 AS IsArchive, 0 AS Status, UpdatedBy, UpdatedOn
                FROM temp_business_info
                WHERE RequestID = '$requestID'
            ) u ON bi.ID = u.TID
            SET
                bi.IsArchive = u.IsArchive,
                bi.Status    = u.Status,
                bi.UpdatedBy = u.UpdatedBy,
                bi.UpdatedOn = u.UpdatedOn
        ");
    }
}

