<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanHaulingKA extends Model
{
    protected $connection = 'mysql_cy';
    protected $table = 'tblplanprodhaul_ka';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'material_desc',
        'total_ka',
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
        'total_ka' => 'double',
        'tonase' => 'decimal:2',
        'create_date' => 'datetime',
        'edit_date' => 'datetime',
    ];
}