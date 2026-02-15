<?php

namespace App\Services;

use App\Contracts\Repositories\PlaceReviewRepositoryContract;
use App\Contracts\Services\PlaceReviewServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class PlaceReviewService implements PlaceReviewServiceContract
{
    public function __construct(private PlaceReviewRepositoryContract $placeReviewRepository)
    {
    }
    public function list(int $placeId, ?int $rating, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->placeReviewRepository->list($placeId, $rating, $page, $perPage);
    }
}
