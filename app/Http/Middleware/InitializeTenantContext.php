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
        if (Auth::check()) {
            $user = Auth::user();
            $tenant = $user->tenant;

            if ($tenant) {
                // 1. Configure Tenant Connection
                config(['database.connections.tenant.database' => $tenant->database_name]);
                
                // 2. Purge & Reconnect
                DB::purge('tenant');
                DB::reconnect('tenant');
                
                // 3. Set Default Connection
                DB::setDefaultConnection('tenant');
            }
        }

        return $next($request);
    }
}
