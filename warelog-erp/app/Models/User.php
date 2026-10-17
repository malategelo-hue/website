<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    // ✅ TINANGGAL ANG HasApiTokens — wala kasing Sanctum!

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // ✅ ITO ANG MAHALAGA
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}