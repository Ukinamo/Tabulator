<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'contestant_id',
        'final_score',
        'rank',
        'is_published',
        'is_revealed',
        'reveal_order',
        'published_at',
        'revealed_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'float',
            'is_published' => 'bool',
            'is_revealed' => 'bool',
            'published_at' => 'datetime',
            'revealed_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function contestant(): BelongsTo
    {
        return $this->belongsTo(Contestant::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeRevealed($query)
    {
        return $query->where('is_revealed', true)->orderBy('reveal_order', 'asc');
    }
}

