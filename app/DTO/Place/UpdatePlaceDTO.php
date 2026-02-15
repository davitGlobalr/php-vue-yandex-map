<?php

namespace App\DTO\Place;

readonly class UpdatePlaceDTO
{

    public function __construct(
        private ?int $status = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }

}
