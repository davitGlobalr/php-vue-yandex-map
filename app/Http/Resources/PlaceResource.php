<?php

namespace App\Http\Resources;

use App\Enums\ParsingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status ?? ParsingStatus::QUEUED;

        return [
            'id' => $this->id,
            'source_org_id' => $this->source_org_id,
            'title' => $this->title,
            'status' => $status->label(),
            'status_value' => $status->value,
            'badge_variant' => $status->badgeVariant(),
            'reviews_count' => $this->reviews_count,
        ];
    }
}
