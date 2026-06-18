<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    // Mengizinkan kolom-kolom ini untuk diisi data secara massal
    protected $fillable = ['name', 'phone_number', 'status'];

    // Relasi ke tabel DriverAssignment (Satu driver bisa punya banyak riwayat penugasan)
    public function assignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }
}
