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
            }
        }

        return $next($request);
    }
}
