<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'exam_id',
        'score',
        'percentage',
        'is_passed',
        'answers',
        'started_at',
        'completed_at'
    ];
    
    protected $casts = [
        'answers' => 'array',
        'is_passed' => 'boolean',
        'score' => 'integer',
        'percentage' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    /**
     * Get the user that owns the exam attempt.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the exam that this attempt belongs to.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    
    /**
     * Check if the exam attempt is completed.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return !is_null($this->completed_at);
    }
    
    /**
     * Calculate the time spent on the exam.
     *
     * @return int Time in minutes
     */
    public function getDurationAttribute()
    {
        if (!$this->completed_at) {
            return null;
        }
        
        return $this->started_at->diffInMinutes($this->completed_at);
    }
    
    /**
     * Get answer for a specific question.
     *
     * @param int $questionId
     * @return mixed|null
     */
    public function getAnswer($questionId)
    {
        return $this->answers[$questionId] ?? null;
    }
    
    /**
     * Check if the attempt was submitted on time.
     *
     * @return bool
     */
    public function isSubmittedOnTime()
    {
        if (!$this->completed_at || !$this->started_at) {
            return false;
        }
        
        $allowedDuration = $this->exam->duration_minutes;
        $actualDuration = $this->duration;
        
        return $actualDuration <= $allowedDuration;
    }
    
    /**
     * Determine if the attempt is still in progress.
     *
     * @return bool
     */
    public function isInProgress()
    {
        return $this->started_at && !$this->completed_at;
    }
    
    /**
     * Get the result status (passed/failed) as a string.
     *
     * @return string
     */
    public function getResultStatusAttribute()
    {
        if (!$this->isCompleted()) {
            return 'In Progress';
        }
        
        return $this->is_passed ? 'Passed' : 'Failed';
    }
}