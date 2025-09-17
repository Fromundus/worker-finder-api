<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_one_id','user_two_id','last_message_at'];

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function userOne(){
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(){
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }
}
