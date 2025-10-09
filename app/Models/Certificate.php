<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'issuing_organization',
        'date_issued',
        'certificate_photo',
    ];

    protected $casts = [
        'date_issued' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
