<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        //        return $this->hasVerifiedEmail();
        return true;
    }

    // SharedDocuments relation with pivots ttl
    public function sharedDocs(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'documents_users', 'user_id', 'document_id')->withPivot('expires_at');
    }

    // SharedDocuments relation with pivots ttl
    public function getSharedDocumentsAttribute(): Collection
    {
        //        $sharedDocs = ;
        return $this->sharedDocs->where('pivot.expires_at', '>', now())->merge($this->sharedDocs->whereNull('pivot.expires_at'));
    }

    // HasMany Documents relation
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'user_id');
    }

    public function getPendingShareInvitationsAttribute(): Collection
    {
        return Document::whereIn('id', DB::table('documents_users')->where('email', $this->email)->where(function (Builder $query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })->pluck('document_id'))->with('user')->get();
    }
}
