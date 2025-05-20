<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'category_id', 
        'name', 
        'slug', 
        'description', 
        'thumbnail_url', 
        'is_svg', 
        'is_active'
    ];
    
    protected $casts = [
        'is_svg' => 'boolean',
        'is_active' => 'boolean'
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}