<?php

namespace App\DTO\Place;

class CreatePlaceDTO
{
    public function __construct(
        private ?int $source_org_id = null,
        private ?int $status = null,
        private ?string $source_url = null,
    ) {
    }

    public function getSourceOrgId(): ?int
    {
        return $this->source_org_id;
    }

    public function toArray(): array
    {
        return [
            'source_org_id' => $this->source_org_id,
            'status' => $this->status,
            'source_url' => $this->source_url,
        ];
    }

}
