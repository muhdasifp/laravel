<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title', 
        'slug', 
        'description', 
        'thumbnail_url', 
        'thumbnail_is_svg', 
        'cover_image_url', 
        'cover_image_is_svg', 
        'category_id', 
        'subject_id', 
        'instructor_id', 
        'level', 
        'language',
        'completed_date', 
        'duration_hours', 
        'total_modules', 
        'total_lessons',
        'total_videos', 
        'total_audios', 
        'total_pdfs', 
        'price', 
        'discount_price',
        'is_free', 
        'is_live', 
        'is_featured', 
        'is_published', 
        'start_date', 
        'end_date'
    ];
    
    protected $casts = [
        'completed_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_free' => 'boolean',
        'is_live' => 'boolean',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'thumbnail_is_svg' => 'boolean',
        'cover_image_is_svg' => 'boolean',
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
    
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    
    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('position');
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
    
    public function exam()
    {
        return $this->hasOne(Exam::class);
    }
    
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
    
    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }
    
    public function getDiscountPercentageAttribute()
    {
        if (!$this->discount_price || $this->price == 0) {
            return 0;
        }
        
        return round((($this->price - $this->discount_price) / $this->price) * 100);
    }
}