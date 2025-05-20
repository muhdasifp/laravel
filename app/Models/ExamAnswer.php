<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'answer',
        'is_correct',
        'marks_awarded',
        'feedback'
    ];
    
    protected $casts = [
        'is_correct' => 'boolean',
        'marks_awarded' => 'integer'
    ];
    
    /**
     * Get the attempt that owns this answer.
     */
    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
    
    /**
     * Get the question this answer is for.
     */
    public function question()
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }
    
    /**
     * Auto-grade the answer if possible based on question type.
     */
    public function autoGrade()
    {
        $question = $this->question;
        
        if ($question->type === 'multiple_choice' || $question->type === 'true_false') {
            $this->is_correct = $question->isCorrect($this->answer);
            $this->marks_awarded = $this->is_correct ? $question->marks : 0;
            $this->save();
            
            return true;
        }
        
        return false; // Manual grading required
    }
}