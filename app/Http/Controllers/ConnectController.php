<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PlaceServiceContract;
use App\DTO\Place\CreatePlaceDTO;
use App\Enums\ParsingStatus;
use App\Http\Requests\ConnectRequest;
use App\Jobs\StartParseJob;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConnectController extends Controller
{
    public function __construct(private readonly PlaceServiceContract $placeService)
    {
    }

    public function show(): Response
    {
        return Inertia::render('admin/ConnectSource');
    }

    public function store(ConnectRequest $request): RedirectResponse
    {
        $sourceUrl = $request->validated('source_url');

        if (!preg_match('/\/(\d+)\/reviews/i', $sourceUrl, $matches)) {

            return redirect()->back()->withErrors([
                'source_url' => 'ID not found.',
            ]);
        }

        $sourceOrgId = (int)$matches[1];

        $place = $this->placeService->create(
            new CreatePlaceDTO(source_org_id: $sourceOrgId, status: ParsingStatus::QUEUED->value)
        );

        StartParseJob::dispatch($place, $sourceUrl, 100);

        return redirect()->back()->with('success', 'Parsing Start!');
    }
}
