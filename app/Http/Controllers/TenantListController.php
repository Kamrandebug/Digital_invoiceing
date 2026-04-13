<?php

namespace App\Http\Controllers;

use App\Models\TenantList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantListController extends Controller
{
    /**
     * List tenants assigned to the logged-in user (via tbl_tenant_users).
     */
    public function index(): View
    {
        $tenants = TenantList::forLoggedInUser();

        return view('pages.dashboards.Tenants.tenant-list', compact('tenants'));
    }

    /**
     * Add/update a tenant for the logged-in user.
     */
    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'BusinessName' => ['required', 'string', 'max:255'],
            'Image'        => ['nullable', 'string', 'max:500'],
        ]);

        $validated['UserID'] = $request->user()?->id ?? auth()->id();

        TenantList::addTenant($validated);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant added/updated successfully.');
    }

    /**
     * Store selected tenant in session and redirect to dashboard.
     */
    public function select(Request $request, int $id): RedirectResponse
    {
        $tenant = TenantList::assignedToUser()
            ->where('tbl_tenant.ID', $id)
            ->first();

        if (!$tenant) {
            return redirect()->route('tenants.index')->with('error', 'Tenant not found or not assigned to you.');
        }

        // Save tenant DB credentials in session so the middleware can switch DB per request.
        // NOTE: Your `digital_invoicing.tbl_tenant` columns are: `DBServer`, `DBUser`, `DBPass`, `DatabaseName`.
        $dbServer = $tenant->DBServer ?? $tenant->dbserver ?? null;
        $dbUser = $tenant->DBUser ?? $tenant->dbuser ?? null;
        $dbPassword = $tenant->DBPass ?? $tenant->DBPassword ?? $tenant->dbpassword ?? null;
        $dbName = $tenant->DatabaseName
            ?? $tenant->database_name
            ?? $tenant->dbname
            ?? $tenant->dbdatabase
            ?? $tenant->db_name
            ?? null;

        $request->session()->put('selected_tenant', [
            'id'         => $tenant->ID,
            'TenantName' => $tenant->TenantName,
            'ImageUrl'   => $tenant->ImageUrl,
            'dbserver'   => $dbServer,
            'dbuser'     => $dbUser,
            'dbpassword' => $dbPassword,
            'dbname'     => $dbName,
            // Membership context from tbl_tenant_users for role-aware authorization.
            'tenant_user_id' => $tenant->TenantUserID ?? null,
            'role'           => strtolower((string) ($tenant->UserRole ?? 'user')),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
