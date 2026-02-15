<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceReview extends Model
{
    protected $fillable = [
        'place_id',
        'source_user_uid',
        'user_name',
        'rating',
        'published_at',
        'review',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
