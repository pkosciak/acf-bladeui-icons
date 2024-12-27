<?php

namespace pkosciak\ACFBladeUIIcons\Providers;

use pkosciak\ACFBladeUIIcons\Models\ACFBladeUIIconsModel;
use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->make(ACFBladeUIIconsModel::class);
    }
}
