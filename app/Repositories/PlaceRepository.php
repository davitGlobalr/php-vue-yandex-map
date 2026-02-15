<?php

namespace App\Repositories;

use App\Contracts\Repositories\PlaceRepositoryContract;
use App\DTO\Place\CreatePlaceDTO;
use App\Models\Place;

class PlaceRepository implements PlaceRepositoryContract
{
    public function __construct(protected readonly Place $model)
    {

    }

    public function findBySourceOrgId($sourceOrgId): ?Place
    {
        return $this->model::where('source_org_id', $sourceOrgId)->first();
    }

    public function update($id, array $data): ?Place
    {
        $place = $this->model::find($id);
        $place->fill($data);
        $place->save();
        return $place;
    }

    public function create(CreatePlaceDTO $createPlaceDTO): ?Place
    {
        return Place::firstOrCreate(
            ['source_org_id' => $createPlaceDTO->getSourceOrgId()],
            $createPlaceDTO->toArray()
        );
    }

}
