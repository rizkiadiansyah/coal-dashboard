<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanObRemoval extends Model
{
    protected $connection = 'mysql_cy';
    protected $table = 'tblobremoval';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'material_desc',
        'tahun',
        'bulan',
        'hari_ke',
        'plan',
        'actual',
        'create_by',
        'create_date',
        'edit_by',
        'edit_date',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'hari_ke' => 'integer',
        'plan' => 'decimal:2',
        'actual' => 'decimal:2',
        'create_date' => 'datetime',
        'edit_date' => 'datetime',
    ];
}
