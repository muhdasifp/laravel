<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'background_image', 
        'background_is_svg', 
        'layout_settings', 
        'is_active'
    ];
    
    protected $casts = [
        'layout_settings' => 'array',
        'is_active' => 'boolean',
        'background_is_svg' => 'boolean'
    ];
    
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}