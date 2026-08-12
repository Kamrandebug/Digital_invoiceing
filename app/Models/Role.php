<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'temp_role';
    protected $primaryKey = 'ID';

    public $timestamps = false;

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'Name',
        'Status',
        'ErrorCode',
        'RequestID',
        'CreatedBy',
        'CreatedOn',
    ];

    public static function getlistingdata($filters = [])
    {
        $query = "SELECT * FROM tbl_role WHERE Status = 1";
        $bindings = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (Name LIKE ? OR Description LIKE ?)";
            $bindings = array_merge($bindings, [$search, $search]);
        }

        $query .= " ORDER BY CreatedOn DESC";

        return DB::connection('tenant')->select($query, $bindings);
    }

    public static function save_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';

        DB::connection('tenant')->statement("
            UPDATE `temp_role` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.`Name`), '') = '' THEN 'Role Name is required'
                        WHEN EXISTS (
                            SELECT 1 FROM `tbl_role` r 
                            WHERE r.Name = t.Name AND r.Status <> 2
                        ) THEN 'Role name already exists'
                        ELSE 'Pass'
                    END AS ErrorCode
                FROM `temp_role` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrorCode = u.ErrorCode
        ", [$requestID]);

        $errorSummary = DB::connection('tenant')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrorCode ORDER BY t.ErrorCode SEPARATOR ', ') AS ErrorCode
            FROM `temp_role` t
            WHERE t.`ErrorCode` IS NOT NULL
              AND t.`ErrorCode` <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrorCode ?: 'Unable to add role due to invalid data.',
            ];
        }

        // Insert new role into tbl_role
        $tempRole = DB::connection('tenant')->table('temp_role')
            ->where('RequestID', $requestID)
            ->first();

        if (!$tempRole) {
            return [
                'status' => false,
                'message' => 'Staged role details not found.',
            ];
        }

        $roleId = DB::connection('tenant')->table('tbl_role')->insertGetId([
            'Name' => $tempRole->Name,
            'Description' => $validated['Description'] ?? null,
            'Status' => 1,
            'CreatedBy' => $tempRole->CreatedBy,
            'CreatedOn' => $tempRole->CreatedOn ?? now(),
            'RequestID' => $requestID,
        ]);

        // Now link details
        DB::connection('tenant')->statement("
            INSERT INTO tbl_role_detail (RoleID, ModuleID, ViewOnly, `Add`, `Modify`, `Delete`, `Print`, `Import`, `Export`, Status, CreatedOn, CreatedBy, RequestID)
            SELECT ?, ModuleID, ViewOnly, `Add`, `Modify`, `Delete`, `Print`, `Import`, `Export`, 1, ?, ?, RequestID
            FROM temp_role_detail
            WHERE RequestID = ?
        ", [$roleId, $tempRole->CreatedOn ?? now(), $tempRole->CreatedBy, $requestID]);

        return [
            'status' => true,
            'message' => null,
            'role_id' => $roleId,
        ];
    }

    public static function edit_transaction($validated)
    {
        $requestID = $validated['RequestID'] ?? '';

        DB::connection('tenant')->statement("
            UPDATE `temp_role` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.`Name`), '') = '' THEN 'Role Name is required'
                        WHEN EXISTS (
                            SELECT 1 FROM `tbl_role` r 
                            WHERE r.Name = t.Name AND r.Status <> 2 AND r.ID <> t.TID
                        ) THEN 'Role name already exists'
                        ELSE 'Pass'
                    END AS ErrorCode
                FROM `temp_role` t
                WHERE t.RequestID = ?
            ) u ON (u.ID = uu.ID)
            SET uu.ErrorCode = u.ErrorCode
        ", [$requestID]);

        $errorSummary = DB::connection('tenant')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrorCode ORDER BY t.ErrorCode SEPARATOR ', ') AS ErrorCode
            FROM `temp_role` t
            WHERE t.`ErrorCode` IS NOT NULL
              AND t.`ErrorCode` <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($errorSummary->cnt ?? 0) > 0) {
            return [
                'status' => false,
                'message' => $errorSummary->ErrorCode ?: 'Unable to update role due to invalid data.',
            ];
        }

        $tempRole = DB::connection('tenant')->table('temp_role')
            ->where('RequestID', $requestID)
            ->first();

        $roleId = $tempRole ? (int)$tempRole->TID : 0;

        // Update tbl_role
        DB::connection('tenant')->table('tbl_role')
            ->where('ID', $roleId)
            ->update([
                'Name' => $tempRole->Name,
                'Description' => $validated['Description'] ?? null,
                'UpdatedBy' => $validated['UpdatedBy'] ?? null,
                'UpdatedOn' => now(),
            ]);

        // Delete old role details
        DB::connection('tenant')->table('tbl_role_detail')
            ->where('RoleID', $roleId)
            ->delete();

        // Insert new role details
        DB::connection('tenant')->statement("
            INSERT INTO tbl_role_detail (RoleID, ModuleID, ViewOnly, `Add`, `Modify`, `Delete`, `Print`, `Import`, `Export`, Status, CreatedOn, CreatedBy, RequestID)
            SELECT ?, ModuleID, ViewOnly, `Add`, `Modify`, `Delete`, `Print`, `Import`, `Export`, 1, ?, ?, RequestID
            FROM temp_role_detail
            WHERE RequestID = ?
        ", [$roleId, now(), $validated['UpdatedBy'] ?? null, $requestID]);

        return [
            'status' => true,
            'message' => null,
        ];
    }
}
