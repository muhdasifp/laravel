<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 
        'course_id', 
        'amount_paid', 
        'payment_method',
        'transaction_id', 
        'enrolled_at', 
        'expiry_date', 
        'is_active'
    ];
    
    protected $casts = [
        'enrolled_at' => 'datetime',
        'expiry_date' => 'date',
        'is_active' => 'boolean'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }
    
    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }
    
    public function getCompletedLessonsAttribute()
    {
        return $this->progress()->where('is_completed', true)->count();
    }
    
    public function getPercentageCompletedAttribute()
    {
        $totalLessons = $this->course->total_lessons;
        
        if ($totalLessons == 0) {
            return 0;
        }
        
        return round(($this->completed_lessons / $totalLessons) * 100, 1);
    }
}