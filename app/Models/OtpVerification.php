<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = ['user_id', 'otp', 'verified', 'expires_at'];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}