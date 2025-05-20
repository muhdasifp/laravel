<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'marks',
        'is_active'
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'marks' => 'integer'
    ];

    /**
     * Get the exam that owns the question.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the answers for this question.
     */
    public function answers()
    {
        return $this->hasMany(ExamAnswer::class, 'question_id');
    }
    /**
     * Get the detailed answers for this attempt.
     */
    public function detailedAnswers()
    {
        return $this->hasMany(ExamAnswer::class, 'exam_attempt_id');
    }
    /**
     * Check if the provided answer is correct.
     *
     * @param string $answer
     * @return bool
     */
    public function isCorrect($answer)
    {
        if ($this->type === 'multiple_choice') {
            return $answer === $this->correct_answer;
        } elseif ($this->type === 'true_false') {
            return strtolower($answer) === strtolower($this->correct_answer);
        } else {
            // For short_answer and essay, might need manual grading
            // or more sophisticated comparison logic
            return strtolower(trim($answer)) === strtolower(trim($this->correct_answer));
        }
    }

    /**
     * Get the available options for multiple choice questions.
     *
     * @return array
     */
    public function getOptionsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return $value ?? [];
    }
}
