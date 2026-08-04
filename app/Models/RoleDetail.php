<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleDetail extends Model
{
    protected $connection = 'tenant';
    protected $table = 'tbl_role_detail';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'RoleID',
        'ModuleID',
        'ViewOnly',
        'Add',
        'Modify',
        'Delete',
        'Print',
        'Import',
        'Export',
        'Status',
        'CreatedOn',
        'CreatedBy',
        'UpdatedOn',
        'UpdatedBy',
        'RequestID',
    ];
}
