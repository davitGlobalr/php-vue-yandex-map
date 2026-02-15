<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PlaceReviewServiceContract;
use App\Contracts\Services\PlaceServiceContract;
use App\Http\Requests\PlaceListRequest;
use App\Http\Requests\PlaceReviewListRequest;
use App\Http\Resources\PlaceResource;
use App\Http\Resources\PlaceReviewResource;
use App\Models\Place;
use Inertia\Inertia;
use Inertia\Response;

class ReviewsController extends Controller
{

    public function __construct(
        private readonly PlaceServiceContract $placeService,
        private readonly PlaceReviewServiceContract $placeReviewService
    ) {
    }

    public function index(PlaceListRequest $request): Response
    {

        $places = $this->placeService->list($request->validated('search'), $request->validated('page', 1),
            $request->validated('per_page', 10));

        return Inertia::render('admin/Reviews', [
            'places' => PlaceResource::collection($places),
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function show(Place $place, PlaceReviewListRequest $request): Response
    {
        $rating = $request->validated('rating');

        $reviews = $this->placeReviewService->list(
            $place->id,
            $rating !== null ? (int) $rating : null,
            $request->validated('page', 1),
            $request->validated('per_page', 3)
        );

        $reviews->withPath(route('admin.place-reviews', ['place' => $place->id]));
        $reviews->appends($request->only(['rating']));

        return Inertia::render('admin/PlaceReviews', [
            'place' => [
                'id' => $place->id,
                'source_org_id' => $place->source_org_id,
                'title' => $place->title,
                'rating_value' => $place->rating_value,
                'rating_count' => $place->rating_count,
            ],
            'reviews' => PlaceReviewResource::collection($reviews),
            'filters' => [
                'rating' => $request->query('rating', ''),
            ],
        ]);
    }
}
