<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    protected $fillable = ['transaksi_id', 'menu_id', 'jumlah', 'subtotal'];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}