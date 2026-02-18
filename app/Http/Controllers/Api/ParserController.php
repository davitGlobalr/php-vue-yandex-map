<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\PlaceServiceContract;
use App\DTO\Place\UpdatePlaceDTO;
use App\Enums\ApiStatus;
use App\Enums\ParsingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ParserImportRequest;
use App\Http\Requests\Api\ParserNeedsManualRequest;
use App\Http\Requests\Api\ParserParseRequest;
use App\Jobs\ImportReviewsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ParserController extends Controller
{
    public function __construct(private readonly PlaceServiceContract $placeService)
    {

    }
    public function import(ParserImportRequest $request): JsonResponse
    {

        $secret = config('services.parser_webhook_secret');
        if (!$secret) {
            return response()->json([
                'error' => 'Webhook secret not configured',
            ], 500);
        }

        $signature = $request->header('X-Parser-Signature');
        if (!$signature) {
            return response()->json([
                'error' => 'Missing signature header',
            ], 401);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        $placeId = $request->validated('place_id');
        $filePath = $request->validated('file_path');
        $placeTitle = $request->validated('place_title');
        $placeRatingValue = $request->validated('place_rating_value');
        $placeRatingCount = $request->validated('place_rating_count');

        $importPath = config('services.import_data_path');
        $fullPath = $importPath . '/' . $filePath;

        if (!file_exists($fullPath)) {
            return response()->json([
                'error' => 'File not found',
                'path' => $fullPath,
            ], 404);
        }

        $place = $this->placeService->findBySourceOrgId($placeId);
        if ($place) {
            $place->update(['status' => ParsingStatus::PARSED]);
        }

        ImportReviewsJob::dispatch($placeId, $filePath, $placeTitle, $placeRatingValue, $placeRatingCount);

        return response()->json([
            'status' => ApiStatus::QUEUED->value,
            'place_id' => $placeId,
            'file_path' => $filePath,
        ], 202);
    }


    public function needsManual(ParserNeedsManualRequest $request): JsonResponse
    {

        $secret = config('services.parser_webhook_secret');
        if (!$secret) {
            return response()->json(['error' => 'Webhook secret fail'], 500);
        }

        $signature = $request->header('X-Parser-Signature');
        if (!$signature) {
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $payload = $request->getContent();
        if (!hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $this->placeService->updateBySourceOrgId($request->validated('place_id'), (new UpdatePlaceDTO(status: ParsingStatus::NEEDS_MANUAL->value))->toArray());

        return response()->json(['status' => ApiStatus::OK->value], 200);
    }


    public function parse(ParserParseRequest $request): JsonResponse
    {
        $parserServiceUrl = config('services.parser_service_url');
        $url = $parserServiceUrl . '/jobs';

        try {
            $response = Http::timeout(5)->post($url, [
                'place_id' => $request->validated('place_id'),
                'url' => $request->validated('url'),
                'max' => $request->validated('max', 50),
            ]);

            if ($response->successful()) {
                return response()->json($response->json(), 202);
            }

            return response()->json([
                'error' => 'Parser service error',
                'message' => $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Failed to send request to parser service', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return response()->json([
                'error' => 'Parser service unavailable',
                'message' => $e->getMessage(),
            ], 503);
        }
    }
}
