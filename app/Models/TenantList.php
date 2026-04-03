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
        'SaleStatus',
        'ExpiryDate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ExpiryDate' => 'datetime',
        ];
    }

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
     * Add/update a tenant record for the current user.
     *
     * Updates the first tenant row with Statusi = 5 for the given user id,
     * setting TenantName from BusinessName and ImageUrl from Image.
     *
     * Must use the landlord connection: DB::unprepared() / default DB uses the
     * tenant session connection, whose MySQL user may not have UPDATE on tbl_tenant.
     */
    public static function addTenant(array $validated = []): void
    {
        $businessName = $validated['BusinessName'] ?? '';
        $image        = $validated['Image'] ?? '';

        $userId = $validated['UserID'] ?? auth()->id();
        if ($userId === null) {
            return;
        }

        $userId = (int) $userId;

        DB::connection('landlord')->update(
            'UPDATE `tbl_tenant` SET `TenantName` = ?, `ImageUrl` = ?, `UserID` = ?, `Statusi` = ? WHERE `Statusi` = ? LIMIT 1',
            [$businessName, $image, $userId, 1, 5]
        );
    }
}
