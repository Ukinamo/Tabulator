<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'weight',
        'description',
        'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class);
    }

    /**
     * Validate that the total weights for all categories in an event sum to 100.
     */
    public static function validateWeightsForEvent(int $eventId): bool
    {
        $total = static::where('event_id', $eventId)->sum('weight');

        return round((float) $total, 2) === 100.00;
    }
}

