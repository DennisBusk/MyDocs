<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;

class Document extends Model
{
    protected $fillable = [
        'name',
        'url',
        'size',
        'extension',
        'mime_type',
        'user_id',
    ];

    // User BelongsTo relation
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // BelongsToMany Users relation
    public function sharedTo()
    {
        return $this->belongsToMany(User::class, 'documents_users', 'document_id', 'user_id')->withPivot('expires_at');
    }
    // BelongsToMany Users relation
    public function getSharedWithAttribute()
    {
        return $this->sharedTo->where('pivot.expires_at', '>', now())->merge($this->sharedTo->whereNull('pivot.expires_at'));

    }
}
