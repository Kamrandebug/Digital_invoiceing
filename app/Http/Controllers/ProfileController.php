<?php

namespace App\Http\Controllers;

use App\Models\TenantUser;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Return AJAX view for profile modal.
     */
    public function modal(Request $request)
    {
        $tab = $request->query('tab', 'info');
        return view('profile._profile-modal-content', [
            'tab' => $tab,
        ]);
    }

    /**
     * Update user profile basic information.
     */
    public function updateInfo(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        // 1. Update central (landlord) user details
        $user->first_name = trim($request->first_name);
        $user->last_name = trim($request->last_name);
        $user->name = trim($request->name);
        $user->mobile = $request->mobile ? trim($request->mobile) : null;
        $user->city = $request->city ? trim($request->city) : null;
        $user->save();

        // 2. Sync active tenant user record via temp_user staging & TenantUser::edit_transaction
        try {
            $tenantUserId = session('selected_tenant.user_id');
            if ($tenantUserId) {
                $tenantUser = DB::connection('tenant')->table('tbl_user')->where('ID', $tenantUserId)->first();
                if ($tenantUser) {
                    $requestId = 'REQ-' . strtoupper(Str::random(10));
                    $now = now();

                    DB::connection('tenant')->table('temp_user')->insert([
                        'TID' => $tenantUser->ID,
                        'Email' => $tenantUser->Email,
                        'FirstName' => $user->first_name,
                        'LastName' => $user->last_name,
                        'Statusi' => $tenantUser->Status ?? 1,
                        'RoleID' => $tenantUser->RoleID,
                        'UpdatedBy' => $tenantUser->ID,
                        'UpdatedOn' => $now,
                        'RequestID' => $requestId,
                    ]);

                    TenantUser::edit_transaction(['RequestID' => $requestId]);

                    // Clean up temp table
                    DB::connection('tenant')->table('temp_user')->where('RequestID', $requestId)->delete();

                    // Log audit trail
                    AuditLogService::log('UPDATE', 'USER', $tenantUser->ID, (array)$tenantUser, [
                        'FirstName' => $user->first_name,
                        'LastName' => $user->last_name,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Tenant user profile sync failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile information updated successfully.',
        ]);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        // Verify current password against landlord users table
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The current password provided is incorrect.',
            ], 422);
        }

        // Update password hash on landlord users table
        $user->password = Hash::make($request->password);
        $user->save();

        // Log audit trail
        try {
            $tenantUserId = session('selected_tenant.user_id') ?: $user->id;
            AuditLogService::log('UPDATE', 'USER', $tenantUserId, [
                'Password' => '******',
            ], [
                'Password' => 'Password Changed Successfully',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Password change audit log failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Legacy edit method fallback.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
