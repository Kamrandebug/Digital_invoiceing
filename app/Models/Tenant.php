<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    /**
     * `tenants` table lives in the landlord DB (same as users).
     */
    protected $connection = 'landlord';

    protected $fillable = [
        'user_id',
        'database_name',
        'domain',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
