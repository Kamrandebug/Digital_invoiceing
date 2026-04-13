<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    /**
     * Ensure user has one of the allowed roles for selected tenant.
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $allowedRoles = array_values(array_filter(array_map(
            static fn ($r) => strtolower(trim((string) $r)),
            $allowedRoles
        )));

        if ($allowedRoles === []) {
            return $next($request);
        }

        $selectedTenant = $request->session()->get('selected_tenant');
        $tenantId = is_array($selectedTenant) ? (int) ($selectedTenant['id'] ?? 0) : 0;
        $userId = (int) (Auth::id() ?? 0);

        if ($tenantId <= 0 || $userId <= 0) {
            return $this->deny($request, 'Please select a tenant first.');
        }

        $role = is_array($selectedTenant)
            ? strtolower(trim((string) ($selectedTenant['role'] ?? '')))
            : '';

        // Fallback lookup if session role is missing/stale.
        if ($role === '') {
            $dbRole = DB::connection('landlord')
                ->table('tbl_tenant_users')
                ->where('TenantID', $tenantId)
                ->where('UserID', $userId)
                ->where('IsActive', 1)
                ->value('Role');

            $role = strtolower(trim((string) ($dbRole ?? '')));
            if ($role !== '') {
                $selectedTenant['role'] = $role;
                $request->session()->put('selected_tenant', $selectedTenant);
            }
        }

        if ($role !== '' && in_array($role, $allowedRoles, true)) {
            return $next($request);
        }

        return $this->deny($request, 'You do not have permission for this tenant action.');
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', $message);
    }
}

