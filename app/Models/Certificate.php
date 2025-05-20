<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 
        'course_id', 
        'certificate_template_id',
        'certificate_number', 
        'certificate_url', 
        'issued_at'
    ];
    
    protected $casts = [
        'issued_at' => 'datetime'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }
}