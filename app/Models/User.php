<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
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
        ];
    }

    public function allows(string $ability): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        $granted = match ($this->role) {
            'manager' => ['sell', 'discount', 'void', 'refund', 'stock', 'purchase', 'reports', 'shift'],
            'cashier' => ['sell', 'discount', 'shift'],
            'storekeeper' => ['stock', 'purchase', 'shift'],
            default => [],
        };

        return in_array($ability, $granted, true);
    }

    public function maxDiscountPercent(): float
    {
        return match ($this->role) {
            'admin', 'manager' => 100.0,
            'cashier' => 5.0,
            default => 0.0,
        };
    }
}
