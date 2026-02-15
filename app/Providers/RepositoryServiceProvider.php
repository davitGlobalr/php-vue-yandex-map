<?php

namespace App\Providers;

use App\Contracts\Repositories\PlaceRepositoryContract;
use App\Contracts\Repositories\PlaceReviewRepositoryContract;
use App\Repositories\PlaceRepository;
use App\Repositories\PlaceReviewRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlaceRepositoryContract::class, PlaceRepository::class);
        $this->app->bind(PlaceReviewRepositoryContract::class, PlaceReviewRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
