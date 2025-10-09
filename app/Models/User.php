<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    
    protected $fillable = [
        // 'first_name',
        // 'middle_name',
        // 'last_name',
        // 'suffix',

        // 'contact_number',
        // 'email',
        // 'email_verified_at',
        // 'password',

        // 'role',
        // 'status',
        // 'employer_type',

        // 'lat',
        // 'lng',
        // 'location_id',

        // 'business_name',

        // 'skills',
        // 'experience',
        // 'average_rating',

        // 'barangay_clearance_photo',
        // 'valid_id_photo',
        // 'selfie_with_id_photo',

        // 'business_permit_photo',
        // 'bir_certificate_photo',

        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'contact_number',
        'email',
        'email_verified_at',
        'password',
        'role',
        "status",

        'sex',
        'religion',
        'civil_status',

        'height',

        'has_disability',
        'disabilities',
        'disability_specify',
        
        "skills",
        "experience",
        
        'average_rating',
        
        'employer_type',
        'business_name',

        'lat',
        'lng',

        'location_id',
        'location_id',

        // Common required images
        'barangay_clearance_photo',
        'valid_id_photo',
        'selfie_with_id_photo',

        // Employer-specific docs
        'business_permit_photo',
        'bir_certificate_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'average_rating' => 'decimal:2',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function feedbackGiven()
    {
        return $this->hasMany(Feedback::class, 'from_user_id');
    }

    public function feedbackReceived()
    {
        return $this->hasMany(Feedback::class, 'to_user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function educations() {
        return $this->hasMany(Education::class);
    }

    public function certificates() {
        return $this->hasMany(Certificate::class);
    }
}
