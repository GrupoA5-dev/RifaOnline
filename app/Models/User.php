<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants, HasAppAuthentication, HasAppAuthenticationRecovery
{
    use HasFactory, Notifiable;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'last_login_at',
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
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => in_array($this->role, [UserRole::SuperAdmin, UserRole::Admin], true),
            'organizer' => $this->organizers()
                ->where('organizers.status', 'active')
                ->exists(),
            default => false,
        };
    }

    /**
     * Only active organizers linked to this user are visible in the organizer panel.
     * Platform roles are deliberately independent of organizer membership.
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($panel->getId() !== 'organizer' || ! $this->active) {
            return collect();
        }

        return $this->organizers()
            ->where('organizers.status', 'active')
            ->get();
    }

    /**
     * Prevent accessing another organizer by guessing its slug in the URL.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->active
            && $tenant instanceof Organizer
            && $tenant->status === 'active'
            && $this->organizers()
                ->whereKey($tenant->getKey())
                ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Organizadores SaaS vinculados ao usuário.
     */
    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class, 'organizer_users')
            ->withPivot('role')
            ->withTimestamps();
    }
}

