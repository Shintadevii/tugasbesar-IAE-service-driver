<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverAssignment extends Model
{
    protected $fillable = ['order_id', 'driver_id', 'status'];

    // Kebalikan relasi: Tugas ini dimiliki oleh driver siapa?
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
