<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Write an audit log entry to the active tenant's tbl_audit_log.
     *
     * @param string $action    E.g. 'CREATE', 'UPDATE', 'DELETE'
     * @param string $module    E.g. 'PRODUCT', 'CUSTOMER', 'INVOICE'
     * @param int|null $recordId Primary key of the target record
     * @param array|null $oldValues Associative array of values before the action
     * @param array|null $newValues Associative array of values after the action
     */
    public static function log($action, $module, $recordId, $oldValues = null, $newValues = null)
    {
        try {
            $userId = session('selected_tenant.user_id') ?: null;

            // Normalize values as arrays
            $oldJson = $oldValues ? json_encode($oldValues) : null;
            $newJson = $newValues ? json_encode($newValues) : null;

            DB::connection('tenant')->table('tbl_audit_log')->insert([
                'UserID' => $userId,
                'Action' => strtoupper($action),
                'Module' => strtoupper($module),
                'RecordID' => $recordId,
                'OldValues' => $oldJson,
                'NewValues' => $newJson,
                'IPAddress' => request()->ip(),
                'UserAgent' => request()->userAgent(),
                'CreatedOn' => now(),
            ]);
        } catch (\Throwable $e) {
            // Log any errors silently to Laravel system log to prevent crashing the main application flow
            Log::error('AuditLogService failed to write log entry: ' . $e->getMessage(), [
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
