<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'locale',
        'content',
        'namespace',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tags associated with this translation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TranslationTag::class, 'translation_tag_pivot')
            ->withTimestamps();
    }

    /**
     * Scope to filter by locale.
     */
    public function scopeByLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope to filter by namespace.
     */
    public function scopeByNamespace(Builder $query, string $namespace): Builder
    {
        return $query->where('namespace', $namespace);
    }

    /**
     * Scope to filter by active status.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search by key or content.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('key', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by tags.
     */
    public function scopeByTags(Builder $query, array $tagNames): Builder
    {
        return $query->whereHas('tags', function ($q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        });
    }
}
