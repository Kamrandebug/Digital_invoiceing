<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
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
