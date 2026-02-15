<?php

namespace App\Providers;


use App\Contracts\Services\PlaceServiceContract;
use App\Contracts\Services\PlaceReviewServiceContract;
use App\Services\PlaceReviewService;
use App\Services\PlaceService;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlaceServiceContract::class, PlaceService::class);
        $this->app->bind(PlaceReviewServiceContract::class, PlaceReviewService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
