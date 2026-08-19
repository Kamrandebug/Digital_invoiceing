<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
        ];
        $users = TenantUser::getlistingdata($filters);

        return view('pages.dashboards.Users.users-list', compact('users'));
    }

    public function create()
    {
        return redirect()->route('users.index');
    }

    public function save_transaction(Request $request)
    {
        $request->validate([
            'Email' => 'required|email|max:100',
            'FirstName' => 'required|string|max:50',
            'LastName' => 'required|string|max:50',
            'RoleID' => 'required|integer',
            'UserType' => 'nullable|string|in:admin,user',
        ]);

        $email = strtolower(trim($request->Email));
        $firstName = trim($request->FirstName);
        $lastName = trim($request->LastName);
        $roleId = (int)$request->RoleID;
        $userType = strtolower(trim($request->input('UserType', 'user')));

        $tenantId = session('selected_tenant.id');
        if (!$tenantId) {
            return response()->json(['status' => 'error', 'message' => 'Active tenant session not found.'], 403);
        }

        $requestId = 'REQ-' . strtoupper(Str::random(10));
        $currentUser = session('selected_tenant.user_id') ?: 1;
        $now = now();

        // 1. Resolve or Create central user in landlord DB
        $centralUser = DB::connection('landlord')->table('users')->where('email', $email)->first();
        if (!$centralUser) {
            $centralUserId = DB::connection('landlord')->table('users')->insertGetId([
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'password' => bcrypt('password123'), // Default temporary password
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $centralUserId = $centralUser->id;
        }

        // 2. Resolve or Create membership in landlord tbl_tenant_users
        $membership = DB::connection('landlord')->table('tbl_tenant_users')
            ->where('TenantID', $tenantId)
            ->where('UserID', $centralUserId)
            ->first();

        if (!$membership) {
            DB::connection('landlord')->table('tbl_tenant_users')->insert([
                'TenantID' => $tenantId,
                'UserID' => $centralUserId,
                'Role' => 'user', // Default central role string fallback
                'IsActive' => 1
            ]);
        } else if ((int)$membership->IsActive !== 1) {
            DB::connection('landlord')->table('tbl_tenant_users')
                ->where('ID', $membership->ID)
                ->update(['IsActive' => 1]);
        }

        // 3. Stage in temp_user inside tenant DB
        $maxId = DB::connection('tenant')->table('temp_user')->max('ID');
        $nextId = $maxId ? $maxId + 1 : 1;

        DB::connection('tenant')->table('temp_user')->insert([
            'ID' => $nextId,
            'TID' => $centralUserId, // Stores central ID temporarily to map into MasterUserID
            'Email' => $email,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Statusi' => 1,
            'RoleID' => $roleId,
            'UserType' => $userType,
            'CreatedBy' => $currentUser,
            'CreatedOn' => $now,
            'RequestID' => $requestId,
        ]);

        // 4. Commit using TenantUser model transaction
        $result = TenantUser::save_transaction(['RequestID' => $requestId]);

        // Cleanup
        DB::connection('tenant')->table('temp_user')->where('RequestID', $requestId)->delete();

        if (!$result['status']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 422);
        }

        // Log save explicitly
        try {
            $inserted = DB::connection('tenant')->table('tbl_user')->where('Email', $email)->where('Status', 1)->first();
            if ($inserted) {
                \App\Services\AuditLogService::log('CREATE', 'USER', $inserted->ID, null, (array)$inserted);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to log user creation: ' . $e->getMessage());
        }

        return response()->json(['status' => 'success', 'message' => 'User added to tenant successfully.']);
    }

    public function edit($id)
    {
        return redirect()->route('users.index');
    }

    public function edit_transaction(Request $request, $id)
    {
        $request->validate([
            'Email' => 'required|email|max:100',
            'FirstName' => 'required|string|max:50',
            'LastName' => 'required|string|max:50',
            'RoleID' => 'required|integer',
            'UserType' => 'nullable|string|in:admin,user',
        ]);

        $email = strtolower(trim($request->Email));
        $firstName = trim($request->FirstName);
        $lastName = trim($request->LastName);
        $roleId = (int)$request->RoleID;
        $userType = strtolower(trim($request->input('UserType', 'user')));

        $requestId = 'REQ-' . strtoupper(Str::random(10));
        $currentUser = session('selected_tenant.user_id') ?: 1;
        $now = now();

        // Fetch old values
        $oldUser = DB::connection('tenant')->table('tbl_user')->where('ID', $id)->first();
        if (!$oldUser) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }

        // Stage in temp_user inside tenant DB
        DB::connection('tenant')->table('temp_user')->insert([
            'TID' => $id, // Stores local user ID to check exclusions
            'Email' => $email,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Statusi' => 1,
            'RoleID' => $roleId,
            'UserType' => $userType,
            'UpdatedBy' => $currentUser,
            'UpdatedOn' => $now,
            'RequestID' => $requestId,
        ]);

        // Commit using TenantUser model transaction
        $result = TenantUser::edit_transaction(['RequestID' => $requestId]);

        // Cleanup
        DB::connection('tenant')->table('temp_user')->where('RequestID', $requestId)->delete();

        if (!$result['status']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 422);
        }

        // Log edit
        try {
            $newUser = DB::connection('tenant')->table('tbl_user')->where('ID', $id)->first();
            \App\Services\AuditLogService::log('UPDATE', 'USER', $id, (array)$oldUser, (array)$newUser);
        } catch (\Throwable $e) {
            \Log::error('Failed to log user edit: ' . $e->getMessage());
        }

        return response()->json(['status' => 'success', 'message' => 'User updated successfully.']);
    }

    public function delete_transaction($id)
    {
        $tenantId = session('selected_tenant.id');
        $currentUser = session('selected_tenant.user_id') ?: 1;

        $user = DB::connection('tenant')->table('tbl_user')->where('ID', $id)->first();
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }

        // Soft delete user in tenant DB
        DB::connection('tenant')->table('tbl_user')->where('ID', $id)->update([
            'Status' => 2,
            'UpdatedBy' => $currentUser,
            'UpdatedOn' => now(),
        ]);

        // Disable membership centrally
        if ($tenantId && $user->MasterUserID) {
            DB::connection('landlord')->table('tbl_tenant_users')
                ->where('TenantID', $tenantId)
                ->where('UserID', $user->MasterUserID)
                ->update(['IsActive' => 0]);
        }

        // Log deletion
        \App\Services\AuditLogService::log('DELETE', 'USER', $id, (array)$user, null);

        return redirect()->route('users.index')->with('success', 'User removed from tenant successfully.');
    }

    public function modal(Request $request, $id = null)
    {
        $mode = $request->query('mode', 'create');
        $permissions = session('selected_tenant.permissions', []);

        if ($mode === 'edit' && (int)($permissions['Users']['Modify'] ?? 0) !== 1) {
            if ($id && (int)($permissions['Users']['ViewOnly'] ?? 0) === 1) {
                $mode = 'show';
            } else {
                return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to edit users.</div>', 403);
            }
        }
        if ($mode === 'create' && (int)($permissions['Users']['Add'] ?? 0) !== 1) {
            return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to add users.</div>', 403);
        }
        if ($mode === 'show' && (int)($permissions['Users']['ViewOnly'] ?? 0) !== 1) {
            return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to view users.</div>', 403);
        }

        $user = null;

        if ($id && in_array($mode, ['edit', 'show'])) {
            $user = DB::connection('tenant')->table('tbl_user')->where('ID', $id)->first();
            if (!$user) {
                return response('<div class="alert alert-danger m-3">User not found.</div>', 404);
            }
        }

        $roles = DB::connection('tenant')->table('tbl_role')->where('Status', 1)->get();

        return view('pages.dashboards.Users._user-form-partial', [
            'mode' => $mode,
            'user' => $user ?? new TenantUser(),
            'roles' => $roles
        ]);
    }
}
