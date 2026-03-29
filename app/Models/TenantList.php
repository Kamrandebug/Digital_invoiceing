<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Tenant list from digital_invoicing.tbl_tenant.
 * Filter by UserID = logged-in user.
 */
class TenantList extends Model
{
    protected $connection = 'landlord';

    protected $table = 'tbl_tenant';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'TenantName',
        'ImageUrl',
        'UserID',
    ];

    /**
     * Scope: tenants assigned to the given user id (default: logged-in user).
     */
    public function scopeAssignedToUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }
        return $query->where('UserID', $userId)->where('Statusi', 1);
    }

    /**
     * Get tenants for the logged-in user.
     */
    public static function forLoggedInUser()
    {
        return static::assignedToUser()->get();
    }

    /**
     * Add/update a tenant record for the current user using a raw UPDATE query.
     *
     * Updates the first tenant row with Status = 5 for the given user id,
     * setting TenantName from BusinessName and ImageUrl from Image.
     *
     * Mirrors the unprepared pattern used in Customer::save_transaction/edit_transaction.
     */
    public static function addTenant(array $validated = []): void
    {
        $businessName = $validated['BusinessName'] ?? '';
        $image        = $validated['Image'] ?? '';

        // Default to the currently authenticated user if no explicit UserID was provided
        $userId = $validated['UserID'] ?? auth()->id();
        if ($userId === null) {
            return;
        }

        // Basic casting before building the raw query
        $userId       = (int) $userId;
        $businessName = addslashes($businessName);
        $image        = addslashes($image);

        DB::unprepared("
            UPDATE digital_invoicing.tbl_tenant
            SET
                TenantName = '{$businessName}',
                ImageUrl   = '{$image}',
                UserID   = '{$userId}',
                Statusi   = '1'
            WHERE Statusi = 5
            LIMIT 1
        ");
    }
}
