<?php

namespace App\Jobs;

use App\Enums\ParsingStatus;
use App\Models\Place;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $placeId,
        public string $filePath
    ) {
    }

    public function handle(): void
    {

        $fullPath = config('services.import_data_path') . '/' . $this->filePath;

        if (!file_exists($fullPath)) {
            return;
        }

        $sourceOrgId = (int) $this->placeId;

        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            return;
        }

        $sourceUrl = null;

        $title = null;
        $ratingValue = null;
        $ratingCount = null;

        while (($line = fgets($handle)) !== false) {
            $data = json_decode(trim($line), true);
            if ($data && !isset($data['error'])) {
                if (!empty($data['source_url'])) {
                    $sourceUrl = $data['source_url'];
                }
                if (!empty($data['place_title'])) {
                    $title = strlen($data['place_title']) > 255 ? substr($data['place_title'], 0, 255) : $data['place_title'];
                }
                if (isset($data['place_rating_value']) && $ratingValue === null) {
                    $ratingValue = is_numeric($data['place_rating_value']) ? (float) $data['place_rating_value'] : null;
                }
                if (isset($data['place_rating_count']) && $ratingCount === null) {
                    $ratingCount = is_numeric($data['place_rating_count']) ? (int) $data['place_rating_count'] : null;
                }
                if ($sourceUrl && ($title !== null || $ratingValue !== null || $ratingCount !== null)) {
                    break;
                }
            }
        }

        rewind($handle);

        $place = Place::firstOrCreate(
            ['source_org_id' => $sourceOrgId],
            [
                'source_url' => $sourceUrl ?? '',
                'title' => $title,
                'rating_value' => $ratingValue,
                'rating_count' => $ratingCount,
                'status' => ParsingStatus::IMPORTING
            ]
        );

        while (($line = fgets($handle)) !== false) {
            $data = json_decode(trim($line), true);

            if (!$data || isset($data['error'])) {
                continue;
            }

            if (empty($data['user_uid'])) {
                continue;
            }

            $rating = isset($data['rating']) ? (int) $data['rating'] : null;
            if ($rating === null || $rating < 1 || $rating > 5) {
                continue;
            }

            $userName = $data['user_name'] ?? '';
            if (strlen($userName) > 255) {
                $userName = substr($userName, 0, 255);
            }

            try {
                DB::table('place_reviews')->insertOrIgnore([
                    'place_id' => $place->id,
                    'source_user_uid' => $data['user_uid'],
                    'user_name' => $userName,
                    'rating' => $rating,
                    'review' => $data['body'] ?? null,
                    'published_at' => $data['published_at'] ? date('Y-m-d H:i:s', strtotime($data['published_at'])) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error("Error importing review: " . $e->getMessage(), [
                    'data' => $data,
                ]);
            }
        }

        fclose($handle);

        if ($place) {
            $place->update(['status' => ParsingStatus::DONE]);
        }
    }
}
