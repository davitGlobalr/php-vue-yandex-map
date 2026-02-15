<?php

namespace App\Contracts\Repositories;

use App\DTO\Place\CreatePlaceDTO;
use App\Models\Place;

interface PlaceRepositoryContract
{
    public function findBySourceOrgId($sourceOrgId): ?Place;

    public function update($id, array $data): ?Place;

    public function create(CreatePlaceDTO $createPlaceDTO): ?Place;
}
