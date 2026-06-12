<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanBargingWBS extends Model
{
    protected $connection = 'mysql_wbs';
    protected $table = 'tblplanprodbarging';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'material_desc',
        'tahun',
        'bulan',
        'hari_ke',
        'tonase',
        'create_by',
        'create_date',
        'edit_by',
        'edit_date',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'hari_ke' => 'integer',
        'tonase' => 'decimal:2',
        'create_date' => 'datetime',
        'edit_date' => 'datetime',
    ];
}
