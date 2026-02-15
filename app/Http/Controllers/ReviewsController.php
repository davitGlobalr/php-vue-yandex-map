<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PlaceServiceContract;
use App\Http\Requests\PlaceListRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use Inertia\Inertia;
use Inertia\Response;

class ReviewsController extends Controller
{

    public function __construct(private readonly PlaceServiceContract $placeService)
    {

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

    public function show(Place $place): Response
    {
        $place->load('reviews');
        $reviews = $place->reviews->map(fn($r) => [
            'id' => $r->id,
            'user_name' => $r->user_name,
            'rating' => $r->rating,
            'review' => $r->review,
            'published_at' => $r->published_at?->format('d.m.Y H:i'),
        ])->sortByDesc(fn($r) => $r['published_at'] ?? '')->values();

        return Inertia::render('admin/PlaceReviews', [
            'place' => [
                'id' => $place->id,
                'source_org_id' => $place->source_org_id,
                'title' => $place->title,
            ],
            'reviews' => $reviews,
        ]);
    }
}
