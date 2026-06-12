<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrusherActivity extends Model
{
    protected $connection = 'mysql_cy';
    protected $table = 'tblcrusheractivity';
    protected $primaryKey = 'id';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'Tanggal' => 'date',
    ];
}