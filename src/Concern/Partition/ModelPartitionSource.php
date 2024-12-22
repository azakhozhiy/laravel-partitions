<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Partition;

use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionSourceInterface;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;

/**
 * @property string $partition_strategy
 *
 * @mixin Model|ModelPartitionSourceInterface
 */
trait ModelPartitionSource
{
    public const string PARTITION_STRATEGY = 'partition_strategy';

    public function getPartitionStrategy(): PartitionStrategyEnum
    {
        return PartitionStrategyEnum::from($this->{static::PARTITION_STRATEGY});
    }

    public static function getSupportedPartitionStrategies(): array
    {
        return [
            PartitionStrategyEnum::HASH_VIA_CODE,
            PartitionStrategyEnum::DEDICATED,
            PartitionStrategyEnum::BASE_TABLE,
        ];
    }

    public static function supportsStrategy(PartitionStrategyEnum $enum): bool
    {
        return in_array($enum, static::getSupportedPartitionStrategies(), true);
    }

    public static function getDefaultPartitionStrategy(): PartitionStrategyEnum
    {
        return PartitionStrategyEnum::HASH_VIA_CODE;
    }
}
