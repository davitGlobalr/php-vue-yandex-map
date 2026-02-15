<?php

namespace App\Contracts\Services;

use App\DTO\Place\CreatePlaceDTO;
use App\Models\Place;

interface PlaceServiceContract
{
    public function findBySourceOrgId($sourceOrgId): ?Place;
    public function updateBySourceOrgId($sourceOrgId, array $data): ?Place;

    public function update($id, array $data): ?Place;
    public function create(CreatePlaceDTO $createPlaceDTO): ?Place;

}
