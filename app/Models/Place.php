<?php

namespace App\Models;

use App\Enums\ParsingStatus;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $fillable = ['source_org_id', 'title', 'source_url', 'rating_value', 'rating_count', 'status'];

    protected $casts = [
        'status' => ParsingStatus::class,
        'status' => ParsingStatus::class,
    ];

    public function reviews()
    {
        return $this->hasMany(PlaceReview::class);
    }
}
