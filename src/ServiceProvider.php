<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/w-partition.php',
            'w-partition'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/w-partition.php' => config_path('w-partition.php'),
        ], 'w-partition');
    }
}
