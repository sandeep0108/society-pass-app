<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
     use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'qr_code_data',
        'purpose',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class);
    }
}
