<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    /**
     * Handle dynamic route authorization using the granular matrix.
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        if (!Auth::check()) {
            return $this->deny($request, 'Please sign in first.');
        }

        $segment = strtolower($request->segment(1) ?? '');
        $moduleMap = [
            'products' => 'Products',
            'customers' => 'Customers',
            'suppliers' => 'Suppliers',
            'invoices' => 'Invoices',
            'purchases' => 'Purchases',
            'users' => 'Users',
            'roles' => 'Roles',
        ];

        if (!isset($moduleMap[$segment])) {
            // Non-catalog pages (like dashboard) let everyone through
            return $next($request);
        }

        $moduleName = $moduleMap[$segment];

        // Resolve active action based on route path / HTTP method
        $action = 'ViewOnly';
        $method = $request->method();
        $path = $request->path();

        if ($method === 'DELETE' || str_contains($path, 'delete')) {
            $action = 'Delete';
        } elseif (($method === 'POST' && str_contains($path, 'save')) || str_contains($path, 'create')) {
            $action = 'Add';
        } elseif ($method === 'PUT' || $method === 'PATCH' || str_contains($path, 'update') || str_contains($path, 'edit')) {
            $action = 'Modify';
        } elseif (str_contains($path, 'print')) {
            $action = 'Print';
        } elseif (str_contains($path, 'import')) {
            $action = 'Import';
        } elseif (str_contains($path, 'export')) {
            $action = 'Export';
        }

        $permissions = session('selected_tenant.permissions', []);

        if (empty($permissions) || !isset($permissions[$moduleName])) {
            return $this->deny($request, 'Access Denied: You do not have permissions for the ' . $moduleName . ' module.');
        }

        $isAuthorized = (int)($permissions[$moduleName][$action] ?? 0) === 1;

        // Fallback: If it's a POST/PUT edit form action but they only have ViewOnly, deny it.
        if ($isAuthorized) {
            return $next($request);
        }

        return $this->deny($request, "Access Denied: You do not have permission to perform this action ($action) in " . $moduleName . '.');
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
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
