<?php

namespace App\Repositories;

use App\Contracts\Repositories\PlaceRepositoryContract;
use App\DTO\Place\CreatePlaceDTO;
use App\Models\Place;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

    public function list(?string $filter, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->model::query()->withCount('reviews')->where(function (Builder $query) use ($filter) {
            $query->where('source_org_id', 'like', "%$filter%")->orWhere('title', 'like', "%$filter%");
        })->paginate($perPage, ['*'], 'page', $page);
    }

}
