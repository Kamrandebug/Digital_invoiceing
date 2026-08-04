<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'tenant';
    protected $table = 'tbl_audit_log';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'Action',
        'Module',
        'RecordID',
        'OldValues',
        'NewValues',
        'IPAddress',
        'UserAgent',
        'CreatedOn',
    ];

    protected $casts = [
        'OldValues' => 'array',
        'NewValues' => 'array',
        'CreatedOn' => 'datetime',
    ];
}
