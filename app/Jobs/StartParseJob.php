<?php

namespace App\Jobs;

use App\Enums\ParsingStatus;
use App\Models\Place;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StartParseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Place $place,
        public string $sourceUrl,
        public int $max = 1000
    ) {
    }

    public function handle(): void
    {

        $parserServiceUrl = config('services.parser_service_url');
        $url = $parserServiceUrl.'/jobs';

        try {
            $response = Http::timeout(3)->post($url, [
                'place_id' => (string)$this->place->source_org_id,
                'url' => $this->sourceUrl,
                'max' => $this->max,
            ]);

            if ($response->successful()) {
                $this->place->update(['status' => ParsingStatus::PARSING]);
                return;
            }
        } catch (\Exception $e) {
            Log::error('StartParseJob: failed to call parser', [
                'place_id' => $this->place->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
