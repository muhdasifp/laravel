<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'course_id', 
        'title', 
        'description', 
        'duration_minutes',
        'pass_percentage', 
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function questions()
    {
        return $this->hasMany(ExamQuestion::class);
    }
    
    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }
}