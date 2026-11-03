<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $connection = 'tenant';
    protected $table = 'tbl_module';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Name',
        'Description',
        'CreatedOn',
        'CreatedBy',
        'UpdatedOn',
        'UpdatedBy',
        'Status',
        'Level',
        'CurrentPage',
        'MainModuleID',
        'ModuleURL',
        'TitleURL',
        'MainModuleIcon',
        'IsReport',
        'IsGlobalSetting',
        'DisplayOrder',
    ];
}
