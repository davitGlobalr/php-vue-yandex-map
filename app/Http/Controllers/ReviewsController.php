<?php

namespace App\Http\Controllers;

use App\Enums\ParsingStatus;
use App\Models\Place;
use Inertia\Inertia;
use Inertia\Response;

class ReviewsController extends Controller
{
    public function index(): Response
    {
        $places = Place::withCount('reviews')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Place $place) {
                $status = $place->status ?? ParsingStatus::QUEUED;

                return [
                    'id' => $place->id,
                    'source_org_id' => $place->source_org_id,
                    'title' => $place->title,
                    'status' => $status->label(),
                    'status_value' => $status->value,
                    'badge_variant' => $status->badgeVariant(),
                    'reviews_count' => $place->reviews_count,
                ];
            });

        return Inertia::render('admin/Reviews', [
            'places' => $places,
        ]);
    }

    public function show(Place $place): Response
    {
        $place->load('reviews');
        $reviews = $place->reviews->map(fn ($r) => [
            'id' => $r->id,
            'user_name' => $r->user_name,
            'rating' => $r->rating,
            'review' => $r->review,
            'published_at' => $r->published_at?->format('d.m.Y H:i'),
        ])->sortByDesc(fn ($r) => $r['published_at'] ?? '')->values();

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
