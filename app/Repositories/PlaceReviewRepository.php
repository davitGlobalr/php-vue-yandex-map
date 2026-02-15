<?php

namespace App\Repositories;

use App\Contracts\Repositories\PlaceReviewRepositoryContract;
use App\Models\PlaceReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
readonly class PlaceReviewRepository implements PlaceReviewRepositoryContract
{
    public function __construct(protected PlaceReview $model)
    {
    }

    public function list(int $placeId, ?int $rating, int $page, int $perPage): LengthAwarePaginator
    {
        $query = $this->model::query()
            ->where('place_id', $placeId)
            ->orderByDesc('published_at');

        if ($rating !== null && in_array($rating, [1, 2, 3, 4, 5], true)) {
            $query->where('rating', $rating);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
