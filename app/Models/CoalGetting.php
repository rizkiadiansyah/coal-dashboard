<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoalGetting extends Model
{
    protected $connection = 'mysql_cy';
    protected $table = 'tblcoaltransaksimasuk';
    protected $primaryKey = 'idmasuk';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'Tanggal'   => 'date',
        'TimeMasuk' => 'datetime',
        'TimeKeluar'=> 'datetime',
    ];
}