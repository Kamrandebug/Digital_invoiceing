<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Role;
use App\Models\RoleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->checkDefaultModules();

        $filters = [
            'search' => $request->input('search'),
        ];
        $roles = Role::getlistingdata($filters);

        return view('pages.dashboards.Roles.roles-list', compact('roles'));
    }

    public function create()
    {
        return redirect()->route('roles.index');
    }

    public function save_transaction(Request $request)
    {
        $request->validate([
            'Name' => 'required|string|max:100',
            'Description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $requestId = 'REQ-' . strtoupper(Str::random(10));
        $userId = session('selected_tenant.user_id') ?: 1;
        $now = now();

        $maxId = DB::connection('tenant')->table('temp_role')->max('ID');
        $nextId = $maxId ? $maxId + 1 : 1;

        DB::connection('tenant')->table('temp_role')->insert([
            'ID' => $nextId,
            'Name' => $request->Name,
            'Status' => 1,
            'CreatedBy' => $userId,
            'CreatedOn' => $now,
            'RequestID' => $requestId,
        ]);

        $permissions = $request->input('permissions', []);
        $modules = DB::connection('tenant')->table('tbl_module')
            ->leftJoin('tbl_role_detail_template', function ($join) {
                $join->on('tbl_module.ID', '=', 'tbl_role_detail_template.ModuleID')
                     ->where('tbl_role_detail_template.TemplateID', '=', 1);
            })
            ->where('tbl_module.Status', 1)
            ->select(
                'tbl_module.ID',
                'tbl_role_detail_template.ViewOnly as TplViewOnly',
                'tbl_role_detail_template.Add as TplAdd',
                'tbl_role_detail_template.Modify as TplModify',
                'tbl_role_detail_template.Delete as TplDelete',
                'tbl_role_detail_template.Print as TplPrint',
                'tbl_role_detail_template.Import as TplImport',
                'tbl_role_detail_template.Export as TplExport'
            )
            ->get();

        foreach ($modules as $mod) {
            $modPerm = $permissions[$mod->ID] ?? [];
            $view = ($mod->TplViewOnly && isset($modPerm['ViewOnly'])) ? 1 : 0;
            $add = ($mod->TplAdd && isset($modPerm['Add'])) ? 1 : 0;
            $modify = ($mod->TplModify && isset($modPerm['Modify'])) ? 1 : 0;
            $delete = ($mod->TplDelete && isset($modPerm['Delete'])) ? 1 : 0;
            $print = ($mod->TplPrint && isset($modPerm['Print'])) ? 1 : 0;
            $import = ($mod->TplImport && isset($modPerm['Import'])) ? 1 : 0;
            $export = ($mod->TplExport && isset($modPerm['Export'])) ? 1 : 0;

            if ($add || $modify || $delete || $print || $import || $export) {
                $view = 1;
            }

            if ($view || $add || $modify || $delete || $print || $import || $export) {
                DB::connection('tenant')->table('temp_role_detail')->insert([
                    'RoleID' => $nextId,
                    'ModuleID' => $mod->ID,
                    'ViewOnly' => $view,
                    'Add' => $add,
                    'Modify' => $modify,
                    'Delete' => $delete,
                    'Print' => $print,
                    'Import' => $import,
                    'Export' => $export,
                    'RequestID' => $requestId,
                ]);
            }
        }

        $result = Role::save_transaction([
            'RequestID' => $requestId,
            'Description' => $request->Description,
        ]);

        // Cleanup
        DB::connection('tenant')->table('temp_role')->where('RequestID', $requestId)->delete();
        DB::connection('tenant')->table('temp_role_detail')->where('RequestID', $requestId)->delete();

        if (!$result['status']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 422);
        }

        // Log save explicitly
        try {
            $inserted = DB::connection('tenant')->table('tbl_role')->where('ID', $result['role_id'])->first();
            if ($inserted) {
                $details = DB::connection('tenant')->table('tbl_role_detail')->where('RoleID', $result['role_id'])->get()->toArray();
                \App\Services\AuditLogService::log('CREATE', 'ROLE', $inserted->ID, null, [
                    'role' => (array)$inserted,
                    'details' => $details
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log role creation: ' . $e->getMessage());
        }

        return response()->json(['status' => 'success', 'message' => 'Role created successfully.']);
    }

    public function edit($id)
    {
        return redirect()->route('roles.index');
    }

    public function edit_transaction(Request $request, $id)
    {
        $request->validate([
            'Name' => 'required|string|max:100',
            'Description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $requestId = 'REQ-' . strtoupper(Str::random(10));
        $userId = session('selected_tenant.user_id') ?: 1;
        $now = now();

        // Fetch old values
        $oldRole = DB::connection('tenant')->table('tbl_role')->where('ID', $id)->first();
        $oldDetails = [];
        if ($oldRole) {
            $oldDetails = DB::connection('tenant')->table('tbl_role_detail')->where('RoleID', $id)->get()->toArray();
        }

        DB::connection('tenant')->table('temp_role')->insert([
            'TID' => $id,
            'Name' => $request->Name,
            'Status' => 1,
            'CreatedBy' => $userId,
            'CreatedOn' => $now,
            'RequestID' => $requestId,
        ]);

        $permissions = $request->input('permissions', []);
        $modules = DB::connection('tenant')->table('tbl_module')
            ->leftJoin('tbl_role_detail_template', function ($join) {
                $join->on('tbl_module.ID', '=', 'tbl_role_detail_template.ModuleID')
                     ->where('tbl_role_detail_template.TemplateID', '=', 1);
            })
            ->where('tbl_module.Status', 1)
            ->select(
                'tbl_module.ID',
                'tbl_role_detail_template.ViewOnly as TplViewOnly',
                'tbl_role_detail_template.Add as TplAdd',
                'tbl_role_detail_template.Modify as TplModify',
                'tbl_role_detail_template.Delete as TplDelete',
                'tbl_role_detail_template.Print as TplPrint',
                'tbl_role_detail_template.Import as TplImport',
                'tbl_role_detail_template.Export as TplExport'
            )
            ->get();

        foreach ($modules as $mod) {
            $modPerm = $permissions[$mod->ID] ?? [];
            $view = ($mod->TplViewOnly && isset($modPerm['ViewOnly'])) ? 1 : 0;
            $add = ($mod->TplAdd && isset($modPerm['Add'])) ? 1 : 0;
            $modify = ($mod->TplModify && isset($modPerm['Modify'])) ? 1 : 0;
            $delete = ($mod->TplDelete && isset($modPerm['Delete'])) ? 1 : 0;
            $print = ($mod->TplPrint && isset($modPerm['Print'])) ? 1 : 0;
            $import = ($mod->TplImport && isset($modPerm['Import'])) ? 1 : 0;
            $export = ($mod->TplExport && isset($modPerm['Export'])) ? 1 : 0;

            if ($add || $modify || $delete || $print || $import || $export) {
                $view = 1;
            }

            if ($view || $add || $modify || $delete || $print || $import || $export) {
                DB::connection('tenant')->table('temp_role_detail')->insert([
                    'RoleID' => $id,
                    'ModuleID' => $mod->ID,
                    'ViewOnly' => $view,
                    'Add' => $add,
                    'Modify' => $modify,
                    'Delete' => $delete,
                    'Print' => $print,
                    'Import' => $import,
                    'Export' => $export,
                    'RequestID' => $requestId,
                ]);
            }
        }

        $result = Role::edit_transaction([
            'RequestID' => $requestId,
            'RoleID' => $id,
            'Description' => $request->Description,
            'UpdatedBy' => $userId,
        ]);

        // Cleanup
        DB::connection('tenant')->table('temp_role')->where('RequestID', $requestId)->delete();
        DB::connection('tenant')->table('temp_role_detail')->where('RequestID', $requestId)->delete();

        if (!$result['status']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 422);
        }

        // Log edit
        try {
            $newRole = DB::connection('tenant')->table('tbl_role')->where('ID', $id)->first();
            $newDetails = DB::connection('tenant')->table('tbl_role_detail')->where('RoleID', $id)->get()->toArray();
            \App\Services\AuditLogService::log('UPDATE', 'ROLE', $id, [
                'role' => (array)$oldRole,
                'details' => $oldDetails
            ], [
                'role' => (array)$newRole,
                'details' => $newDetails
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to log role edit: ' . $e->getMessage());
        }

        return response()->json(['status' => 'success', 'message' => 'Role updated successfully.']);
    }

    public function delete_transaction($id)
    {
        $userId = session('selected_tenant.user_id') ?: 1;

        // Fetch old values
        $oldRole = DB::connection('tenant')->table('tbl_role')->where('ID', $id)->first();
        if (!$oldRole) {
            return redirect()->route('roles.index')->with('error', 'Role not found.');
        }

        $oldDetails = DB::connection('tenant')->table('tbl_role_detail')->where('RoleID', $id)->get()->toArray();

        DB::connection('tenant')->table('tbl_role')->where('ID', $id)->update([
            'Status' => 2,
            'UpdatedBy' => $userId,
            'UpdatedOn' => now(),
        ]);

        // Log deletion
        \App\Services\AuditLogService::log('DELETE', 'ROLE', $id, [
            'role' => (array)$oldRole,
            'details' => $oldDetails
        ], null);

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    public function modal(Request $request, $id = null)
    {
        $mode = $request->query('mode', 'create');
        $permissions = session('selected_tenant.permissions', []);

        if ($mode === 'edit' && (int)($permissions['Roles']['Modify'] ?? 0) !== 1) {
            if ($id && (int)($permissions['Roles']['ViewOnly'] ?? 0) === 1) {
                $mode = 'show';
            } else {
                return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to edit roles.</div>', 403);
            }
        }
        if ($mode === 'create' && (int)($permissions['Roles']['Add'] ?? 0) !== 1) {
            return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to add roles.</div>', 403);
        }
        if ($mode === 'show' && (int)($permissions['Roles']['ViewOnly'] ?? 0) !== 1) {
            return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to view roles.</div>', 403);
        }

        $role = null;
        $details = collect();

        if ($id && in_array($mode, ['edit', 'show'])) {
            $role = DB::connection('tenant')->table('tbl_role')->where('ID', $id)->first();
            if (!$role) {
                return response('<div class="alert alert-danger m-3">Role not found.</div>', 404);
            }
            $details = DB::connection('tenant')->table('tbl_role_detail')->where('RoleID', $id)->get()->keyBy('ModuleID');
        }

        $modules = DB::connection('tenant')->table('tbl_module')
            ->leftJoin('tbl_role_detail_template', function ($join) {
                $join->on('tbl_module.ID', '=', 'tbl_role_detail_template.ModuleID')
                     ->where('tbl_role_detail_template.TemplateID', '=', 1);
            })
            ->where('tbl_module.Status', 1)
            ->orderBy('tbl_module.DisplayOrder', 'asc')
            ->select(
                'tbl_module.*',
                'tbl_role_detail_template.ViewOnly as TplViewOnly',
                'tbl_role_detail_template.Add as TplAdd',
                'tbl_role_detail_template.Modify as TplModify',
                'tbl_role_detail_template.Delete as TplDelete',
                'tbl_role_detail_template.Print as TplPrint',
                'tbl_role_detail_template.Import as TplImport',
                'tbl_role_detail_template.Export as TplExport'
            )
            ->get();

        return view('pages.dashboards.Roles._role-form-partial', [
            'mode' => $mode,
            'role' => $role ?? new Role(),
            'modules' => $modules,
            'roleDetails' => $details
        ]);
    }

    private function checkDefaultModules()
    {
        $count = Module::count();
        if ($count === 0) {
            $defaultModules = [
                ['Name' => 'Products', 'Description' => 'Product Catalogue', 'ModuleURL' => '/products', 'CurrentPage' => 'products', 'DisplayOrder' => 1, 'CreatedBy' => 1],
                ['Name' => 'Customers', 'Description' => 'Customer Directory', 'ModuleURL' => '/customers', 'CurrentPage' => 'customers', 'DisplayOrder' => 2, 'CreatedBy' => 1],
                ['Name' => 'Suppliers', 'Description' => 'Supplier Directory', 'ModuleURL' => '/suppliers', 'CurrentPage' => 'suppliers', 'DisplayOrder' => 3, 'CreatedBy' => 1],
                ['Name' => 'Invoices', 'Description' => 'Sales Invoicing', 'ModuleURL' => '/invoices', 'CurrentPage' => 'invoices', 'DisplayOrder' => 4, 'CreatedBy' => 1],
                ['Name' => 'Purchases', 'Description' => 'Purchase Orders and Receipts', 'ModuleURL' => '/purchases', 'CurrentPage' => 'purchases', 'DisplayOrder' => 5, 'CreatedBy' => 1],
                ['Name' => 'Users', 'Description' => 'Tenant User Management', 'ModuleURL' => '/users', 'CurrentPage' => 'users', 'DisplayOrder' => 6, 'CreatedBy' => 1],
                ['Name' => 'Roles', 'Description' => 'Tenant Roles and Rights Configuration', 'ModuleURL' => '/roles', 'CurrentPage' => 'roles', 'DisplayOrder' => 7, 'CreatedBy' => 1],
            ];
            foreach ($defaultModules as $mod) {
                Module::create($mod);
            }
        }
    }
}
