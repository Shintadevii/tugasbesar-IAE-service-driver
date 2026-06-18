<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'branch_name',
        'address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}