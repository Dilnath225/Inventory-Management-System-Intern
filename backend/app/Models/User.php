<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cupboards created by this user
     */
    public function cupboards()
    {
        return $this->hasMany(Cupboard::class, 'created_by');
    }

    /**
     * Places created by this user
     */
    public function places()
    {
        return $this->hasMany(Place::class, 'created_by');
    }

    /**
     * Items created by this user
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'created_by');
    }

    /**
     * Borrowings created by this user
     */
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'created_by');
    }

    /**
     * Audit logs for this user
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
