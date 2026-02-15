<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlaceReviewRepositoryContract
{
    public function list(int $placeId, ?int $rating, int $page, int $perPage): LengthAwarePaginator;
}
