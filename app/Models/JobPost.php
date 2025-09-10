<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'title',
        'description',
        'job_type',
        'salary',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}
