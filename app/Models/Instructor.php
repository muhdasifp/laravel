<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'slug', 
        'bio', 
        'profile_picture', 
        'profile_picture_is_svg', 
        'email', 
        'phone', 
        'website', 
        'social_links', 
        'is_active'
    ];
    
    protected $casts = [
        'social_links' => 'array',
        'profile_picture_is_svg' => 'boolean',
        'is_active' => 'boolean'
    ];
    
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}