<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TranslationTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the translations associated with this tag.
     */
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class, 'translation_tag_pivot')
            ->withTimestamps();
    }
}
