<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the role associated with the user.
     *
     * @return BelongsTo<Role, User>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if the user is an Administrator (id: 1).
     */
    public function isAdmin(): bool
    {
        return (int) $this->role_id === Role::ADMIN;
    }

    /**
     * Check if the user is a standard User (id: 2).
     */
    public function isUser(): bool
    {
        return (int) $this->role_id === Role::USER;
    }

    /**
     * Check if the user has a specific role by id, slug, or name.
     */
    public function hasRole(int|string $role): bool
    {
        if (is_numeric($role)) {
            return (int) $this->role_id === (int) $role;
        }

        $needle = strtolower(trim($role));
        $roleName = strtolower($this->role?->name ?? ($this->isAdmin() ? 'admin' : 'user'));
        $roleSlug = strtolower($this->role?->slug ?? ($this->isAdmin() ? 'admin' : 'user'));

        return $needle === $roleName || $needle === $roleSlug;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => (int) $this->role_id,
            'role' => $this->role?->name ?? ($this->isAdmin() ? 'Admin' : 'User'),
        ];
    }

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
            'role_id' => 'integer',
        ];
    }
}
