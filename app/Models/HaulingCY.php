<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HaulingCY extends Model
{
    protected $connection = 'mysql_cy';
    protected $table = 'tblkirimcytransaksikirim';
    protected $primaryKey = 'idmasuk';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'Tanggal'        => 'date',
        'TimeMasuk'      => 'datetime',
        'TimeKeluar'     => 'datetime',
        'WaktuTiba'      => 'datetime',
        'WaktuBerangkat' => 'datetime',
    ];
}