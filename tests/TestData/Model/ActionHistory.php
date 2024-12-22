<?php

namespace AZakhozhiy\Laravel\Partitions\Tests\TestData\Model;

use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Concern\Partition\ModelPartitioned;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\Service\ModelPartitionedSettings;

class ActionHistory extends Model implements ModelPartitionedInterface
{
    use ModelPartitioned;

    public static function getPartitionedSettings(): ModelPartitionedSettings
    {
        // TODO: Implement getPartitionedSettings() method.
    }

    public static function getPartitionSourceStrategies(): array
    {
        // TODO: Implement getPartitionSourceStrategies() method.
    }

    public static function hasPartitionNumberColumn(): bool
    {
        // TODO: Implement hasPartitionNumberColumn() method.
    }
}