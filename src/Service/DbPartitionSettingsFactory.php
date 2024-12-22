<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Service;

use Illuminate\Support\Arr;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionDbStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\HashDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\RangeDbPartitionSettings;

class DbPartitionSettingsFactory
{
    public static function fromConfig(array $config): BaseDbPartitionSettings
    {
        $strategy = Arr::get($config, BaseDbPartitionSettings::FIELD_STRATEGY);

        $strategyEnum = PartitionDbStrategyEnum::tryFrom($strategy);

        if (is_null($strategyEnum)) {
            throw new RuntimeException("Db partition strategy $strategy is not defined.");
        }

        $dbPartition = null;

        if ($strategyEnum === PartitionDbStrategyEnum::RANGE) {
            $dbPartition = RangeDbPartitionSettings::createByConfig($config);
        }

        if ($strategyEnum === PartitionDbStrategyEnum::HASH) {
            $dbPartition = HashDbPartitionSettings::createByConfig($config);
        }

        if ($dbPartition === null) {
            throw new RuntimeException("Unsupported partition db strategy.");
        }

        $additionalConfig = Arr::get($config, BaseDbPartitionSettings::FIELD_ADDITIONAL);

        if ($additionalConfig) {
            $additionalDbPartition = static::fromConfig($additionalConfig);
            $dbPartition->setAdditional($additionalDbPartition);
        }

        return $dbPartition;
    }
}
