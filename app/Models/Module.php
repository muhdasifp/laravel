<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'course_id', 
        'title', 
        'description', 
        'thumbnail', 
        'thumbnail_is_svg',
        'audio_file', 
        'video_file', 
        'sample_file', 
        'listen_duration', 
        'position',
        'is_active', 
        'is_live', 
        'pdf_files'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_live' => 'boolean',
        'pdf_files' => 'array',
        'thumbnail_is_svg' => 'boolean'
    ];
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }
}