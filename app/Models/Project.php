<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'employer',
        'description',
        'date_started',
        'date_ended',
        'picture',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
