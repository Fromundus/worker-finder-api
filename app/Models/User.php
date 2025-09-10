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
        'name',
        'contact_number',
        'email',
        'password',
        'role',
        'status',
        'skills',
        'experience',
        'average_rating',
        'business_name',
        'lat',
        'lng',
        'address',
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
}
