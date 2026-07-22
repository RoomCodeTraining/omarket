<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'role', 'can_publish', 'partner_approved_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'role' => UserRole::class,
            'can_publish' => 'boolean',
            'partner_approved_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin || $this->is_admin === true;
    }

    public function isPartner(): bool
    {
        return $this->role === UserRole::Partner;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function canPublishProducts(): bool
    {
        return $this->isAdmin() || ($this->isPartner() && $this->can_publish);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customRequests(): HasMany
    {
        return $this->hasMany(CustomRequest::class);
    }

    public function scopePartners(Builder $query): Builder
    {
        return $query->where('role', UserRole::Partner);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->where('role', UserRole::Admin)
                ->orWhere('is_admin', true);
        });
    }
}
