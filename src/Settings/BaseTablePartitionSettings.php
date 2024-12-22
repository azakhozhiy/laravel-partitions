<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings;

use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Exception\PartitionByNumberSupportException;

class BaseTablePartitionSettings extends BasePartitionSettings
{
    public static function createByDefaultConfig(array $config): static
    {
        return (new static())
            ->fillByBaseConfig($config);
    }

    public function getTableNameByPartitionKeys(string|int ...$keys): string
    {
        return $this->baseTablePattern;
    }

    public static function getPartitionStrategyEnum(): PartitionStrategyEnum
    {
        return PartitionStrategyEnum::BASE_TABLE;
    }

    public function getTableNameByPartitionNumber(int $number): string
    {
        throw new PartitionByNumberSupportException(
            "Db hash partition does not support partition by number."
        );
    }

    public function getDefaultTableName(): string
    {
        return $this->getTableNameByPartitionKeys();
    }
}
