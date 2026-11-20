<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = ['total_bayar', 'uang_diterima', 'kembalian'];

    public function details()
    {
        return $this->hasMany(DetailTransaksi::class);
    }
}