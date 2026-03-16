<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'venue',
        'event_date',
        'status',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contestants(): HasMany
    {
        return $this->hasMany(Contestant::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }
}

