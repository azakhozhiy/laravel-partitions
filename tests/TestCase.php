<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use AZakhozhiy\Laravel\Partitions\Tests\TestData\ServiceProvider;
use AZakhozhiy\Laravel\Partitions\ServiceProvider as PartitionServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PartitionServiceProvider::class,
        ];
    }
}
