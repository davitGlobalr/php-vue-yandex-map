<?php

namespace App\Contracts\Repositories;

use App\DTO\Place\CreatePlaceDTO;
use App\Models\Place;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlaceRepositoryContract
{
    public function findBySourceOrgId($sourceOrgId): ?Place;

    public function update($id, array $data): ?Place;

    public function create(CreatePlaceDTO $createPlaceDTO): ?Place;

    public function list(?string $filter, int $page, int $perPage): LengthAwarePaginator;
}
