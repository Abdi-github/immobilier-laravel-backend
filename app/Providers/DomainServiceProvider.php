<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Translation provider contract
        $this->app->bind(
            \App\Domain\Translation\Contracts\TranslationProviderInterface::class,
            function ($app) {
                $primary = config('immobilier.translation.primary_provider', 'libretranslate');

                return $primary === 'deepl'
                    ? $app->make(\App\Domain\Translation\Providers\DeepLProvider::class)
                    : $app->make(\App\Domain\Translation\Providers\LibreTranslateProvider::class);
            },
        );
    }

    public function boot(): void
    {
        // Register observers
        \App\Domain\Property\Models\Property::observe(\App\Domain\Property\Observers\PropertyObserver::class);
        \App\Domain\Agency\Models\Agency::observe(\App\Domain\Agency\Observers\AgencyObserver::class);
    }
}
