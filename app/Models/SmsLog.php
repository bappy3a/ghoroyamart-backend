<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'mobile_no',
        'message',
        'ip',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];
}
