<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Session-driven tenant switch (from `digital_invoicing.tbl_tenant`).
        $selectedTenant = $request->session()->get('selected_tenant');
        if (
            is_array($selectedTenant)
            && array_key_exists('dbserver', $selectedTenant)
            && array_key_exists('dbuser', $selectedTenant)
            && array_key_exists('dbpassword', $selectedTenant)
            && $selectedTenant['dbserver'] !== null
            && $selectedTenant['dbuser'] !== null
            && $selectedTenant['dbpassword'] !== null
        ) {
            // Ensure the runtime `tenant` connection uses selected credentials.
            config([
                // Avoid precedence issues if `DATABASE_URL` is ever set.
                'database.connections.tenant.url' => null,
                'database.connections.tenant.host' => $selectedTenant['dbserver'],
                'database.connections.tenant.username' => $selectedTenant['dbuser'],
                'database.connections.tenant.password' => $selectedTenant['dbpassword'],
                'database.connections.tenant.database' => $selectedTenant['dbname'] ?: env('DB_DATABASE', 'digital_invoicing'),
            ]);

            // Purge & Reconnect so Laravel picks up the new credentials.
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Set default connection so all tenant models use the correct DB.
            DB::setDefaultConnection('tenant');

            // Resolve Tenant User mapping
            $this->resolveTenantUser($request);

            return $next($request);
        }

        // 2) Fallback: existing user->tenant based switching (database-per-tenant provisioning).
        if (Auth::check()) {
            $user = Auth::user();
            $tenant = $user->tenant;

            if ($tenant) {
                // Configure Tenant Connection (database only; host/user/pass stay from the `tenant` template).
                config(['database.connections.tenant.database' => $tenant->database_name]);

                DB::purge('tenant');
                DB::reconnect('tenant');

                DB::setDefaultConnection('tenant');

                // Resolve Tenant User mapping
                $this->resolveTenantUser($request);
            }
        }

        return $next($request);
    }

    private function resolveTenantUser(Request $request)
    {
        if (Auth::check()) {
            try {
                $tenantUser = DB::connection('tenant')->table('tbl_user')
                    ->where('MasterUserID', Auth::id())
                    ->first();

                if (!$tenantUser) {
                    // Fallback to Email mapping
                    $tenantUser = DB::connection('tenant')->table('tbl_user')
                        ->where('Email', Auth::user()->email)
                        ->first();

                    if ($tenantUser) {
                        // Update MasterUserID for future requests
                        DB::connection('tenant')->table('tbl_user')
                            ->where('ID', $tenantUser->ID)
                            ->update(['MasterUserID' => Auth::id()]);
                        
                        $tenantUser = DB::connection('tenant')->table('tbl_user')->where('ID', $tenantUser->ID)->first();
                    } else {
                        // Self-provision tenant user
                        $roleId = DB::connection('tenant')->table('tbl_role')->where('Name', 'Owner')->value('ID');
                        if (!$roleId) {
                            $this->seedDefaultRolesAndModules();
                            $roleId = DB::connection('tenant')->table('tbl_role')->where('Name', 'Owner')->value('ID');
                        }

                        $names = explode(' ', Auth::user()->name ?? 'Portal User', 2);
                        $firstName = $names[0] ?? 'Portal';
                        $lastName = $names[1] ?? 'User';

                        $tenantUserId = DB::connection('tenant')->table('tbl_user')->insertGetId([
                            'MasterUserID' => Auth::id(),
                            'Email' => Auth::user()->email,
                            'FirstName' => $firstName,
                            'LastName' => $lastName,
                            'RoleID' => $roleId ?: null,
                            'Status' => 1,
                            'CreatedBy' => 1,
                            'CreatedOn' => now(),
                        ]);
                        $tenantUser = DB::connection('tenant')->table('tbl_user')->where('ID', $tenantUserId)->first();
                    }
                }

                if ($tenantUser) {
                    $userType = strtolower(trim((string)($tenantUser->UserType ?? 'user')));
                    $request->session()->put('selected_tenant.user_id', $tenantUser->ID);
                    $request->session()->put('selected_tenant.role_id', $tenantUser->RoleID);
                    $request->session()->put('selected_tenant.user_type', $userType);

                    // Load active modular permissions into session
                    if ($userType === 'admin') {
                        $allModules = DB::connection('tenant')->table('tbl_module')
                            ->pluck('Name');

                        $adminPermissions = [];
                        foreach ($allModules as $modName) {
                            $adminPermissions[$modName] = [
                                'ViewOnly' => 1,
                                'Add' => 1,
                                'Modify' => 1,
                                'Delete' => 1,
                                'Print' => 1,
                                'Import' => 1,
                                'Export' => 1,
                            ];
                        }
                        $request->session()->put('selected_tenant.permissions', $adminPermissions);
                    } elseif ($tenantUser->RoleID) {
                        $roleDetails = DB::connection('tenant')->table('tbl_role_detail')
                            ->join('tbl_module', 'tbl_role_detail.ModuleID', '=', 'tbl_module.ID')
                            ->where('tbl_role_detail.RoleID', $tenantUser->RoleID)
                            ->where('tbl_role_detail.Status', 1)
                            ->select('tbl_module.Name as ModuleName', 'tbl_role_detail.*')
                            ->get()
                            ->keyBy('ModuleName')
                            ->map(function ($row) {
                                return [
                                    'ViewOnly' => (int)$row->ViewOnly,
                                    'Add' => (int)$row->Add,
                                    'Modify' => (int)$row->Modify,
                                    'Delete' => (int)$row->Delete,
                                    'Print' => (int)$row->Print,
                                    'Import' => (int)$row->Import,
                                    'Export' => (int)$row->Export,
                                ];
                            })
                            ->toArray();

                        $request->session()->put('selected_tenant.permissions', $roleDetails);
                    } else {
                        $request->session()->put('selected_tenant.permissions', []);
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('InitializeTenantContext failed to map tenant user: ' . $e->getMessage());
            }
        }
    }

    private function seedDefaultRolesAndModules()
    {
        // Seed modules first if empty
        $count = DB::connection('tenant')->table('tbl_module')->count();
        if ($count === 0) {
            $defaultModules = [
                ['Name' => 'Products', 'Description' => 'Product Catalogue', 'ModuleURL' => '/products', 'CurrentPage' => 'products', 'DisplayOrder' => 1, 'CreatedBy' => 1],
                ['Name' => 'Customers', 'Description' => 'Customer Directory', 'ModuleURL' => '/customers', 'CurrentPage' => 'customers', 'DisplayOrder' => 2, 'CreatedBy' => 1],
                ['Name' => 'Suppliers', 'Description' => 'Supplier Directory', 'ModuleURL' => '/suppliers', 'CurrentPage' => 'suppliers', 'DisplayOrder' => 3, 'CreatedBy' => 1],
                ['Name' => 'Invoices', 'Description' => 'Sales Invoicing', 'ModuleURL' => '/invoices', 'CurrentPage' => 'invoices', 'DisplayOrder' => 4, 'CreatedBy' => 1],
                ['Name' => 'Purchases', 'Description' => 'Purchase Orders and Receipts', 'ModuleURL' => '/purchases', 'CurrentPage' => 'purchases', 'DisplayOrder' => 5, 'CreatedBy' => 1],
                ['Name' => 'Users', 'Description' => 'Tenant User Management', 'ModuleURL' => '/users', 'CreatedBy' => 1],
                ['Name' => 'Roles', 'Description' => 'Tenant Roles and Rights Configuration', 'ModuleURL' => '/roles', 'CreatedBy' => 1],
            ];
            foreach ($defaultModules as $mod) {
                DB::connection('tenant')->table('tbl_module')->insert($mod);
            }
        }

        // Seed Roles
        $ownerRoleId = DB::connection('tenant')->table('tbl_role')->insertGetId([
            'Name' => 'Owner',
            'Description' => 'Tenant business owner with full access',
            'Status' => 1,
            'CreatedBy' => 1,
            'CreatedOn' => now(),
        ]);

        $adminRoleId = DB::connection('tenant')->table('tbl_role')->insertGetId([
            'Name' => 'Admin',
            'Description' => 'Administrator with full system control',
            'Status' => 1,
            'CreatedBy' => 1,
            'CreatedOn' => now(),
        ]);

        $accountantRoleId = DB::connection('tenant')->table('tbl_role')->insertGetId([
            'Name' => 'Accountant',
            'Description' => 'Financial operations role',
            'Status' => 1,
            'CreatedBy' => 1,
            'CreatedOn' => now(),
        ]);

        // Insert full access for Owner and Admin, financial access for Accountant
        $modules = DB::connection('tenant')->table('tbl_module')->get();
        foreach ($modules as $mod) {
            // Owner gets full rights
            DB::connection('tenant')->table('tbl_role_detail')->insert([
                'RoleID' => $ownerRoleId,
                'ModuleID' => $mod->ID,
                'ViewOnly' => 1, 'Add' => 1, 'Modify' => 1, 'Delete' => 1, 'Print' => 1, 'Import' => 1, 'Export' => 1,
                'Status' => 1, 'CreatedBy' => 1, 'CreatedOn' => now()
            ]);

            // Admin gets full rights
            DB::connection('tenant')->table('tbl_role_detail')->insert([
                'RoleID' => $adminRoleId,
                'ModuleID' => $mod->ID,
                'ViewOnly' => 1, 'Add' => 1, 'Modify' => 1, 'Delete' => 1, 'Print' => 1, 'Import' => 1, 'Export' => 1,
                'Status' => 1, 'CreatedBy' => 1, 'CreatedOn' => now()
            ]);

            // Accountant gets full rights on Products, Customers, Suppliers, Invoices, Purchases, but ViewOnly/None on Users and Roles
            $isFinance = in_array($mod->Name, ['Products', 'Customers', 'Suppliers', 'Invoices', 'Purchases']);
            DB::connection('tenant')->table('tbl_role_detail')->insert([
                'RoleID' => $accountantRoleId,
                'ModuleID' => $mod->ID,
                'ViewOnly' => 1,
                'Add' => $isFinance ? 1 : 0,
                'Modify' => $isFinance ? 1 : 0,
                'Delete' => $isFinance ? 1 : 0,
                'Print' => 1,
                'Import' => $isFinance ? 1 : 0,
                'Export' => 1,
                'Status' => 1, 'CreatedBy' => 1, 'CreatedOn' => now()
            ]);
        }
    }
}
