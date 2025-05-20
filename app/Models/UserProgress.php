<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'enrollment_id', 
        'lesson_id', 
        'is_completed',
        'progress_percentage', 
        'completed_at', 
        'last_accessed_at'
    ];
    
    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime'
    ];
    
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
    
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}