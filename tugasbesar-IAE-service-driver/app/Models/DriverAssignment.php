<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverAssignment extends Model
{
    // Membuka izin agar semua kolom penting bisa diisi secara massal
    protected $fillable = [
        'order_id', 
        'driver_id', 
        'tracking_number', // Ditambahkan agar rute pencarian resi bisa bekerja
        'status'
    ];

    /**
     * Kebalikan relasi: Tugas ini dimiliki oleh driver siapa?
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}