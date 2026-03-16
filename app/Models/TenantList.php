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
        return $query->where('UserID', $userId);
    }

    /**
     * Get tenants for the logged-in user.
     */
    public static function forLoggedInUser()
    {
        return static::assignedToUser()->get();
    }
}
