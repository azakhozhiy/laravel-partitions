<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings;

use AZakhozhiy\Laravel\Partitions\Concern\Settings\HasHashViaCodePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Helper\PartitionHashHelper;

class HashViaCodePartitionSettings extends BasePartitionSettings
{
    use HasHashViaCodePartitionSettings;

    public static function createByDefaultConfig(array $config): static
    {
        return (new static())
            ->fillByBaseConfig($config)
            ->fillByHashViaCodeConfig($config);
    }

    public function getAllTablesNames(): array
    {
        $firstNumber = $this->getHashViaCodePartitionFirstNumber();
        $partitionsCount = $this->getHashViaCodePartitionCount();

        $tables = [];

        for ($i = $firstNumber; $i <= $firstNumber + $partitionsCount; $i++) {
            $tables[] = $this->getTableNameByPartitionNumber($i);
        }

        return $tables;
    }

    public function getPartitionNumberByKeys(string|int ...$keys): int
    {
        $preparedKey = implode($this->getHashViaCodePartitionKeysDelimiter(), $keys);

        return PartitionHashHelper::getPartitionNumberByKey(
            $preparedKey,
            $this->hashViaCodePartitionCount
        );
    }

    public function getTableNameByPartitionKeys(string|int ...$keys): string
    {
        return sprintf($this->baseTablePattern, $this->getPartitionNumberByKeys(...$keys));
    }

    public static function getPartitionStrategyEnum(): PartitionStrategyEnum
    {
        return PartitionStrategyEnum::HASH_VIA_CODE;
    }

    public function getTableNameByPartitionNumber(int $number): string
    {
        return sprintf($this->baseTablePattern, $number);
    }

    public function getDefaultTableName(): string
    {
        return sprintf($this->baseTablePattern, $this->hashViaCodePartitionDefaultNumber);
    }
}
