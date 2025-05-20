<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'module_id', 
        'title', 
        'description', 
        'video_url', 
        'audio_url',
        'content', 
        'duration_minutes', 
        'position', 
        'is_free_preview', 
        'is_active'
    ];
    
    protected $casts = [
        'is_free_preview' => 'boolean',
        'is_active' => 'boolean'
    ];
    
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    
    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }
}