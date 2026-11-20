<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'status',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organizer_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Only campaigns explicitly owned by this organizer.
     */
    public function raffles(): HasMany
    {
        return $this->hasMany(Raffle::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
