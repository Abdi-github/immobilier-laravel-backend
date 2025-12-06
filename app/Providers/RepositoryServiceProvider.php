<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Property domain
        $this->app->bind(
            \App\Domain\Property\Repositories\PropertyRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentPropertyRepository::class,
        );
        $this->app->bind(
            \App\Domain\Property\Repositories\PropertyImageRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentPropertyImageRepository::class,
        );

        // User domain
        $this->app->bind(
            \App\Domain\User\Repositories\UserRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentUserRepository::class,
        );
        $this->app->bind(
            \App\Domain\User\Repositories\FavoriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentFavoriteRepository::class,
        );
        $this->app->bind(
            \App\Domain\User\Repositories\AlertRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentAlertRepository::class,
        );

        // Agency domain
        $this->app->bind(
            \App\Domain\Agency\Repositories\AgencyRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentAgencyRepository::class,
        );

        // Lead domain
        $this->app->bind(
            \App\Domain\Lead\Repositories\LeadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentLeadRepository::class,
        );

        // Location domain
        $this->app->bind(
            \App\Domain\Location\Repositories\CantonRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentCantonRepository::class,
        );
        $this->app->bind(
            \App\Domain\Location\Repositories\CityRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentCityRepository::class,
        );

        // Catalog domain
        $this->app->bind(
            \App\Domain\Catalog\Repositories\CategoryRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository::class,
        );
        $this->app->bind(
            \App\Domain\Catalog\Repositories\AmenityRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\EloquentAmenityRepository::class,
        );
    }
}
