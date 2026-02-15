<?php

namespace App\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlaceReviewServiceContract
{
    public function list(int $placeId, ?int $rating, int $page, int $perPage): LengthAwarePaginator;
}
