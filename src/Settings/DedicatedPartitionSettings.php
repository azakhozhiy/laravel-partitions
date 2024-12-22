<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings;

use AZakhozhiy\Laravel\Partitions\Concern\Settings\HasDedicatedPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Exception\PartitionByNumberSupportException;
use AZakhozhiy\Laravel\Partitions\Exception\PartitionTableException;

class DedicatedPartitionSettings extends BasePartitionSettings
{
    use HasDedicatedPartitionSettings;

    public static function createByDefaultConfig(array $config): static
    {
        return (new static())
            ->fillByBaseConfig($config)
            ->fillByDedicatedConfig($config);
    }

    public function getTableNameByPartitionKeys(string|int ...$keys): string
    {
        $preparedKey = implode('', $keys);

        return sprintf($this->baseTablePattern, $preparedKey);
    }

    public static function getPartitionStrategyEnum(): PartitionStrategyEnum
    {
        return PartitionStrategyEnum::DEDICATED;
    }

    public function getTableNameByPartitionNumber(int $number): string
    {
        throw new PartitionByNumberSupportException(
            "Dedicated partition does not support partition by number."
        );
    }

    public function getDefaultTableName(): string
    {
        throw new PartitionTableException(
            "Dedicated partition doesn't have default table."
        );
    }
}
