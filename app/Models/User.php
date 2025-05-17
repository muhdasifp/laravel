<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * User status constants
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    
    /**
     * User role constants
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';
    const ROLE_MANAGER = 'manager';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'mobile_no',
        'password',
        'fcm_id',
        'name',
        'role',
        'image_url',
        'token',
        'address',
        'dob',
        'gender',
        'school_name',
        'roll_no',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'token',
        'remember_token',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'role' => self::ROLE_USER,  // Default role is user
        'status' => self::STATUS_ACTIVE,  // Default status is active (1)
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'dob' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }
    
    /**
     * Check if user is admin
     * 
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }
    
    /**
     * Check if user is manager
     * 
     * @return bool
     */
    public function isManager()
    {
        return $this->role === self::ROLE_MANAGER;
    }
    
    /**
     * Check if user is regular user
     * 
     * @return bool
     */
    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }
    
    /**
     * Check if user is active
     * 
     * @return bool
     */
    public function isActive()
    {
        return (int)$this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * Get role name
     * 
     * @return string
     */
    public function getRoleName()
    {
        switch ($this->role) {
            case self::ROLE_ADMIN:
                return 'Admin';
            case self::ROLE_MANAGER:
                return 'Manager';
            case self::ROLE_USER:
                return 'User';
            default:
                return 'Unknown';
        }
    }
    
    /**
     * Get status name
     * 
     * @return string
     */
    public function getStatusName()
    {
        switch ((int)$this->status) {
            case self::STATUS_ACTIVE:
                return 'Active';
            case self::STATUS_INACTIVE:
                return 'Inactive';
            default:
                return 'Unknown';
        }
    }
}