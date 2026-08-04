<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TenantUser extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'temp_user';
    protected $primaryKey = 'ID';

    public $timestamps = false;

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'TID',
        'Email',
        'FirstName',
        'LastName',
        'Statusi',
        'RoleID',
        'ErrorCode',
        'RequestID',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn',
    ];

    public static function getlistingdata($filters = [])
    {
        $query = "SELECT u.*, r.Name as RoleName 
                  FROM tbl_user u 
                  LEFT JOIN tbl_role r ON u.RoleID = r.ID 
                  WHERE u.Status = 1";
        $bindings = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (u.FirstName LIKE ? OR u.LastName LIKE ? OR u.Email LIKE ?)";
            $bindings = array_merge($bindings, [$search, $search, $search]);
        }

        $query .= " ORDER BY u.CreatedOn DESC";

        return DB::connection('tenant')->select($query, $bindings);
    }

    public static function save_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';

        DB::connection('tenant')->statement("
            UPDATE `temp_user` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.`Email`), '') = '' THEN 'Email is required'
                        WHEN EXISTS (
                            SELECT 1 FROM `tbl_user` u 
                            WHERE u.Email = t.Email AND u.Status <> 2
                        ) THEN 'Email already registered for this tenant'
                        ELSE 'Pass'
                    END AS ErrorCode
                FROM `temp_user` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrorCode = u.ErrorCode
        ", [$requestID]);

        $errorSummary = DB::connection('tenant')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrorCode ORDER BY t.ErrorCode SEPARATOR ', ') AS ErrorCode
            FROM `temp_user` t
            WHERE t.`ErrorCode` IS NOT NULL
              AND t.`ErrorCode` <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrorCode ?: 'Unable to add user due to invalid data.',
            ];
        }

        // We will insert from temp_user into tbl_user
        DB::connection('tenant')->statement("
            INSERT INTO tbl_user (MasterUserID, Email, FirstName, LastName, Status, RoleID, CreatedBy, CreatedOn, RequestID)
            SELECT TID, Email, FirstName, LastName, 1, RoleID, CreatedBy, CreatedOn, RequestID
            FROM temp_user
            WHERE RequestID = ?
        ", [$requestID]);

        return [
            'status' => true,
            'message' => null,
        ];
    }

    public static function edit_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';

        DB::connection('tenant')->statement("
            UPDATE `temp_user` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.`Email`), '') = '' THEN 'Email is required'
                        WHEN EXISTS (
                            SELECT 1 FROM `tbl_user` u 
                            WHERE u.Email = t.Email AND u.Status <> 2 AND u.ID <> t.TID
                        ) THEN 'Email already registered for this tenant'
                        ELSE 'Pass'
                    END AS ErrorCode
                FROM `temp_user` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrorCode = u.ErrorCode
        ", [$requestID]);

        $errorSummary = DB::connection('tenant')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrorCode ORDER BY t.ErrorCode SEPARATOR ', ') AS ErrorCode
            FROM `temp_user` t
            WHERE t.`ErrorCode` IS NOT NULL
              AND t.`ErrorCode` <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrorCode ?: 'Unable to update user due to invalid data.',
            ];
        }

        DB::connection('tenant')->statement("
            UPDATE tbl_user uu
            JOIN temp_user u ON uu.ID = u.TID
            SET
                uu.Email = u.Email,
                uu.FirstName = u.FirstName,
                uu.LastName = u.LastName,
                uu.RoleID = u.RoleID,
                uu.UpdatedBy = u.UpdatedBy,
                uu.UpdatedOn = u.UpdatedOn
            WHERE u.RequestID = ?
        ", [$requestID]);

        return [
            'status' => true,
            'message' => null,
        ];
    }
}
