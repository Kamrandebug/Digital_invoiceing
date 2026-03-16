<?php

namespace App\Http\Controllers;

use App\Models\TenantList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantListController extends Controller
{
    /**
     * List tenants assigned to the logged-in user (from tbl_tenant, UserID = auth id).
     */
    public function index(): View
    {
        $tenants = TenantList::forLoggedInUser();

        return view('pages.dashboards.Tenants.tenant-list', compact('tenants'));
    }

    /**
     * Store selected tenant in session and redirect to dashboard.
     */
    public function select(Request $request, int $id): RedirectResponse
    {
        $tenant = TenantList::assignedToUser()->find($id);

        if (!$tenant) {
            return redirect()->route('tenants.index')->with('error', 'Tenant not found or not assigned to you.');
        }

        $request->session()->put('selected_tenant', [
            'id'         => $tenant->ID,
            'TenantName' => $tenant->TenantName,
            'ImageUrl'   => $tenant->ImageUrl,
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
