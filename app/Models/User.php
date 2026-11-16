<?php

namespace App\Models;

use App\Models\Concerns\HasVendors;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasVendors, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    // is_platform_admin is deliberately NOT fillable: set it by hand in the database or tinker.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
