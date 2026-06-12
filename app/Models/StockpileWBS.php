<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockpileWBS extends Model
{
    protected $connection = 'mysql_wbs';
    protected $table = 'tbltransaksimasuk';
    protected $primaryKey = 'idmasuk';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'TimeMasuk'            => 'datetime',
        'TimeKeluar'           => 'datetime',
        'WaktuBerangkatMerapi' => 'datetime',
        'WaktuTerimaPort'      => 'datetime',
    ];
}