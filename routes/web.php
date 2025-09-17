<?php

use App\Mail\RegisteredMemberMail;
use App\Models\RegisteredMember;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});