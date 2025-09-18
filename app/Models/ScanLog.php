<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'scanned_data',
        'is_valid_pass',
        'notes',
        'scanned_by_user_id',
        'scanner_ip',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function scannedByUser()
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }
}
