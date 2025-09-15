<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'employer_id',
        'worker_id',
        'job_title',
        'description',
        'salary',
        'status',
        'workerIsRated',
        'employerIsRated',
    ];

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}
