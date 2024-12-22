<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Service;

use RuntimeException;
use Throwable;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Settings\BasePartitionSettings;

class ModelPartitionedSettings
{
    /** @var callable[] */
    protected array $settings = [];

    /**
     * @param  array  $partitionedModelConfig
     * @param  PartitionStrategyEnum[]  $sourcePartitionStrategies  if [], model supports all strategies
     */
    public function __construct(array $partitionedModelConfig, array $sourcePartitionStrategies = [])
    {
        $defaultSettings = [];

        foreach ($sourcePartitionStrategies as $strategy) {
            $strategyConfig = config('w-partition.strategies.'.$strategy->arrayKey());

            if ($strategyConfig) {
                $defaultSettings[] = $strategyConfig;
            }

            if (!isset($partitionedModelConfig[$strategy->arrayKey()])) {
                throw new RuntimeException(
                    "Partitioned model should support source partition strategy $strategy->value."
                );
            }
        }

        $settings = array_merge($defaultSettings, $partitionedModelConfig);

        $this->fillSettingsByConfig($settings);
    }

    protected function fillSettingsByConfig($settings): void
    {
        foreach ($settings as $strategyArrayKey => $config) {
            try {
                $strategyEnum = PartitionStrategyEnum::fromArrayKey($strategyArrayKey);
            } catch (Throwable) {
                continue;
            }

            $this->settings[$strategyEnum->value] = static fn() => $strategyEnum->createSettingsObj($config);
        }
    }

    /**
     * @return BasePartitionSettings[]
     */
    public function getAll(): array
    {
        $settings = [];
        foreach ($this->settings as $strategyName => $settingFn) {
            /** @var BasePartitionSettings $settingsObj */
            $settingsObj = $this->settings[$strategyName]();

            $settingsObj->ensureValidState();
            $settings[$strategyName] = $settingsObj;
        }

        return $settings;
    }

    public function getByStrategy(PartitionStrategyEnum $enum): BasePartitionSettings
    {
        if (isset($this->settings[$enum->value])) {
            /** @var BasePartitionSettings $settingsObj */
            $settingsObj = $this->settings[$enum->value]();

            $settingsObj->ensureValidState();

            return $settingsObj;
        }

        throw new RuntimeException("Unknown or unsupported strategy $enum->value.");
    }
}
