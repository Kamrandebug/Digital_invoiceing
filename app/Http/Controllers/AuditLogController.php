<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * Show the global audit logs page.
     */
    public function index(Request $request)
    {
        $query = DB::connection('tenant')->table('tbl_audit_log as a')
            ->leftJoin('tbl_user as u', 'a.UserID', '=', 'u.ID')
            ->select('a.*', DB::raw("COALESCE(NULLIF(TRIM(CONCAT(u.FirstName, ' ', u.LastName)), ''), 'System') as UserName"));

        // Searching
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('a.Action', 'LIKE', $search)
                  ->orWhere('a.Module', 'LIKE', $search)
                  ->orWhere('u.FirstName', 'LIKE', $search)
                  ->orWhere('u.LastName', 'LIKE', $search)
                  ->orWhere('a.IPAddress', 'LIKE', $search);
            });
        }

        // Action Filter
        if ($request->filled('action_filter')) {
            $query->where('a.Action', $request->action_filter);
        }

        // Module Filter
        if ($request->filled('module_filter')) {
            $query->where('a.Module', $request->module_filter);
        }

        $logs = $query->orderBy('a.CreatedOn', 'desc')->paginate(15)->withQueryString();

        // Get modules and actions for filter dropdowns
        $modules = DB::connection('tenant')->table('tbl_audit_log')->distinct()->pluck('Module');
        $actions = DB::connection('tenant')->table('tbl_audit_log')->distinct()->pluck('Action');

        return view('pages.dashboards.AuditLogs.index', compact('logs', 'modules', 'actions'));
    }

    /**
     * Get AJAX history for a specific record.
     */
    public function history(Request $request)
    {
        $module = $request->query('module');
        $recordId = $request->query('record_id');

        if (!$module || !$recordId) {
            return response()->json(['status' => 'error', 'message' => 'Module and Record ID are required.'], 400);
        }

        $moduleMap = [
            'PRODUCT' => 'Products',
            'CUSTOMER' => 'Customers',
            'SUPPLIER' => 'Suppliers',
            'USER' => 'Users',
            'ROLE' => 'Roles',
            'INVOICE' => 'Invoices',
            'PURCHASE' => 'Purchases',
        ];
        $permModule = $moduleMap[strtoupper($module)] ?? ucfirst(strtolower($module));
        $permissions = session('selected_tenant.permissions', []);
        if (isset($permissions[$permModule]) && (int)($permissions[$permModule]['ViewOnly'] ?? 0) !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Access Denied: You do not have permission to view audit history for this module.'], 403);
        }

        $logs = DB::connection('tenant')->table('tbl_audit_log as a')
            ->leftJoin('tbl_user as u', 'a.UserID', '=', 'u.ID')
            ->select('a.*', DB::raw("COALESCE(NULLIF(TRIM(CONCAT(u.FirstName, ' ', u.LastName)), ''), 'System') as UserName"))
            ->where('a.Module', strtoupper($module))
            ->where('a.RecordID', $recordId)
            ->orderBy('a.CreatedOn', 'desc')
            ->get();

        // Retrieve lookup maps for user-friendly translations
        $roles = DB::connection('tenant')->table('tbl_role')->pluck('Name', 'ID');
        
        $categories = collect();
        foreach (['tbl_category', 'tbl_Category', 'Tbl_category'] as $tbl) {
            try {
                $categories = DB::connection('tenant')->table($tbl)->pluck('CategoryName', 'ID');
                if ($categories->isNotEmpty()) break;
            } catch (\Exception $e) {}
        }

        $units = collect();
        foreach (['tbl_unit', 'tbl_Unit', 'Tbl_unit'] as $tbl) {
            try {
                $units = DB::connection('tenant')->table($tbl)->pluck('Name', 'ID');
                if ($units->isNotEmpty()) break;
            } catch (\Exception $e) {}
        }

        return response()->json([
            'status' => 'success',
            'data' => $logs,
            'lookups' => [
                'RoleID' => $roles,
                'CategoryID' => $categories,
                'Category' => $categories,
                'UnitID' => $units,
                'Unit' => $units,
            ]
        ]);
    }
}
