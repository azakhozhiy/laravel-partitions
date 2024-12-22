<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Partition;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Concern\Composite\HasCompositePrimaryKey;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Exception\PartitionQueryException;
use AZakhozhiy\Laravel\Partitions\Service\ModelPartitionedSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;

/**
 * @mixin Model
 * @mixin ModelPartitionedInterface
 */
trait ModelPartitioned
{
    use HasCompositePrimaryKey;

    public const string PARTITION_NUMBER = 'partition_number';

    abstract public static function getPartitionedSettings(): ModelPartitionedSettings;

    /**
     * @return PartitionStrategyEnum[]
     */
    abstract public static function getPartitionSourceStrategies(): array;

    abstract public static function hasPartitionNumberColumn(): bool;

    public static function getPartitionNumberColumn(): ?string
    {
        return static::hasPartitionNumberColumn() ? static::PARTITION_NUMBER : null;
    }

    protected function setCompositeKeyByStrategySettings(BaseDbPartitionSettings $settings): static
    {
        return $this->setKeyNames($settings->getCompositeKeys());
    }

    public static function queryByPartitionKey(PartitionStrategyEnum $enum, int|string ...$keys): Builder
    {
        $strategySettings = static::getPartitionedSettings()->getByStrategy($enum);

        if (!$strategySettings::supportsQueryByPartitionKey()) {
            throw new PartitionQueryException(
                "$enum->value partition strategy doesn't support query by partition key."
            );
        }

        $instance = (new static())
            ->setTableByPartitionKeys($enum, ...$keys);

        if ($strategySettings->getDbPartition()) {
            $instance->setKeyNames($strategySettings->getDbPartition()->getCompositeKeys());
        }

        return $instance->newQuery();
    }

    public static function queryByPartitionNumber(PartitionStrategyEnum $enum, int $number = 1): Builder
    {
        /** @var HashViaCodePartitionSettings $strategySettings */
        $strategySettings = static::getPartitionedSettings()->getByStrategy($enum);

        if (!$strategySettings::supportsQueryByPartitionNumber()) {
            throw new PartitionQueryException(
                "$enum->value partition strategy doesn't support query by partition number."
            );
        }

        if ($number === 1) {
            $number = $strategySettings->getHashViaCodePartitionDefaultNumber();
        }

        $table = $strategySettings->getTableNameByPartitionNumber($number);
        $instance = (new static())->setTable($table);

        if ($strategySettings->getDbPartition()) {
            $instance->setKeyNames($strategySettings->getDbPartition()->getCompositeKeys());
        }

        return $instance->newQuery();
    }

    public static function createByPartitionKeys(PartitionStrategyEnum $enum, string|int ...$keys): static
    {
        $strategySettings = static::getPartitionedSettings()->getByStrategy($enum);

        if (!$strategySettings::supportsQueryByPartitionKey()) {
            throw new PartitionQueryException(
                "$enum->value partition strategy doesn't support create model by partition key."
            );
        }

        $instance = (new static())->setTableByPartitionKeys($enum, ...$keys);

        if ($strategySettings instanceof HashViaCodePartitionSettings && $instance::hasPartitionNumberColumn()) {
            $instance->{$instance::getPartitionNumberColumn()} = $strategySettings->getPartitionNumberByKeys(...$keys);
        }

        if ($strategySettings->getDbPartition()) {
            $instance->setKeyNames($strategySettings->getDbPartition()->getCompositeKeys());
        }

        return $instance;
    }

    public function setTableByPartitionKeys(PartitionStrategyEnum $enum, string|int ...$keys): static
    {
        $strategySettings = static::getPartitionedSettings()->getByStrategy($enum);
        $table = $strategySettings->getTableNameByPartitionKeys(...$keys);

        $this->setTable($table);

        if ($strategySettings->getDbPartition()) {
            $this->setCompositeKeyByStrategySettings($strategySettings->getDbPartition());
        }

        return $this;
    }

    public static function getDefaultPartitionStrategy(): ?PartitionStrategyEnum
    {
        return PartitionStrategyEnum::HASH_VIA_CODE;
    }

    /**
     * @return Builder
     */
    public static function query(?PartitionStrategyEnum $strategyEnum = null, string|int|null ...$keys)
    {
        if ($strategyEnum && isset($keys[0])) {
            return static::queryByPartitionKey($strategyEnum, ...$keys);
        }

        $defaultStrategy = static::getDefaultPartitionStrategy();

        if (!$defaultStrategy) {
            throw new PartitionQueryException(
                "Model doesn't have default partition strategy. ".
                "Please use queryByPartitionKey or queryByPartitionNumber for partitioned model queries."
            );
        }

        if (!$defaultStrategy->canUseWithoutPartitionKeys()) {
            throw new PartitionQueryException(
                "This partition strategy does not use without partition keys."
            );
        }

        if ($defaultStrategy->isHashViaCode()) {
            return static::queryByPartitionNumber($defaultStrategy);
        }

        return static::queryByPartitionKey($defaultStrategy);
    }
}
