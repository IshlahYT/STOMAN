<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 1;

    public const USER = 2;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the users associated with this role.
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
