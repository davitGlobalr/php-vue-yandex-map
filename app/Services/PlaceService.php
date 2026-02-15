<?php

namespace App\Services;

use App\Contracts\Repositories\PlaceRepositoryContract;
use App\Contracts\Services\PlaceServiceContract;
use App\DTO\Place\CreatePlaceDTO;
use App\Models\Place;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class PlaceService implements PlaceServiceContract
{

    public function __construct(private PlaceRepositoryContract $placeRepository)
    {
    }

    public function findBySourceOrgId($sourceOrgId): ?Place
    {
        return $this->placeRepository->findBySourceOrgId($sourceOrgId);
    }

    public function updateBySourceOrgId($sourceOrgId, array $data): ?Place
    {
        $place = $this->placeRepository->findBySourceOrgId($sourceOrgId);
        if (!$place) {
            return null;
        }
        return $this->placeRepository->update($place->id, $data);
    }

    public function update($id, array $data): ?Place
    {
        return $this->placeRepository->update($id, $data);
    }

    public function create(CreatePlaceDTO $createPlaceDTO): ?Place
    {
        return $this->placeRepository->create($createPlaceDTO);
    }

    public function list(?string $filter, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->placeRepository->list($filter, $page, $perPage);
    }

}
