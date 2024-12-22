<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Service;

use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionSourceInterface;
use AZakhozhiy\Laravel\Partitions\Database\Schema;
use AZakhozhiy\Laravel\Partitions\DTO\CreatedTableDTO;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Facade\PartitionSchema;
use AZakhozhiy\Laravel\Partitions\Helper\DbPartitionHelper;
use AZakhozhiy\Laravel\Partitions\Helper\DbTableHelper;
use AZakhozhiy\Laravel\Partitions\Settings\BasePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\BaseTablePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\HashDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\RangeDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;

class ModelMigrationService
{
    private static function validatePartitionedModelClass(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new RuntimeException("Model class doesn't exist.");
        }

        if (!in_array(ModelPartitionedInterface::class, class_implements($modelClass), true)) {
            throw new RuntimeException(
                "Model class must implement ".ModelPartitionedInterface::class
            );
        }
    }

    private static function validatePartitionSourceModelClass(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new RuntimeException("Partition source model class doesn't exist.");
        }

        if (!in_array(ModelPartitionSourceInterface::class, class_implements($modelClass), true)) {
            throw new RuntimeException(
                "Partition source model must implement ".ModelPartitionSourceInterface::class
            );
        }
    }

    /**
     * @param  class-string<ModelPartitionedInterface>  $partitionedModelClass
     */
    public static function createDedicatedTableBySettings(
        ModelPartitionSourceInterface $partitionSource,
        string $partitionedModelClass,
        callable $blueprintFn
    ): CreatedTableDTO {
        static::validatePartitionedModelClass($partitionedModelClass);

        if (!$partitionSource::supportsStrategy(PartitionStrategyEnum::DEDICATED)) {
            throw new RuntimeException("Partition source is not supported dedicated strategy.");
        }

        $dedicatedSettings = $partitionedModelClass::getPartitionedSettings()
            ->getByStrategy(PartitionStrategyEnum::DEDICATED);

        $tableName = $dedicatedSettings->getTableNameByPartitionKeys(
            $partitionSource->{$partitionSource->getPartitionKey()}
        );

        if ($dbPartition = $dedicatedSettings->getDbPartition()) {
            $createdTable = PartitionService::createRootPartitionedTable(
                $tableName,
                $dbPartition,
                $blueprintFn
            );
        } else {
            PartitionSchema::create($tableName, $blueprintFn);
            $createdTable = new CreatedTableDTO($tableName);
        }

        return $createdTable;
    }

    /**
     * @param  class-string<ModelPartitionedInterface>  $modelClass
     */
    public static function deleteTablesBySettings(string $modelClass): void
    {
        static::validatePartitionedModelClass($modelClass);
        $partitionSettings = $modelClass::getPartitionedSettings()->getAll();

        /** @var BasePartitionSettings $partitionSetting */
        foreach ($partitionSettings as $partitionSetting) {
            if ($partitionSetting instanceof BaseTablePartitionSettings) {
                $tables[] = $partitionSetting->getBaseTablePattern();
            } else {
                $tables = DbPartitionHelper::getAllRootTablesByPattern(
                    $partitionSetting->getBaseTablePattern()
                )->toArray();
            }

            foreach ($tables as $table) {
                Schema::dropIfExists($table);
            }
        }
    }

    /**
     * @param  class-string<ModelPartitionedInterface>  $modelClass
     * @param  int  $count
     * @param  int  $additionalCount
     * @return array
     */
    public static function createNextRangePartitionTables(
        string $modelClass,
        int $count = 1,
        int $additionalCount = 1
    ): array {
        static::validatePartitionedModelClass($modelClass);

        $partitionSettings = $modelClass::getPartitionedSettings()->getAll();

        $created = [];

        /** @var BasePartitionSettings $partitionSetting */
        foreach ($partitionSettings as $partitionSetting) {
            /** @var BaseDbPartitionSettings|RangeDbPartitionSettings $dbPartition */
            $dbPartition = $partitionSetting->getDbPartition();
            $dbPartitionStrategy = $dbPartition?->getStrategy();

            if ($dbPartition === null) {
                continue;
            }

            $rootTablePattern = $partitionSetting->getBaseTablePattern();
            $dbPartitionSuffix = $dbPartition->getTableSuffixPattern();

            if ($dbPartitionStrategy->isRange()) {
                $complexPattern = $rootTablePattern.$dbPartitionSuffix;
                $regexPattern = DbTableHelper::buildRegexTablePattern($complexPattern);
                $created[$complexPattern]['items'] =
                    PartitionService::createNextRangePartitionsByTableRegexPattern(
                        $count,
                        $regexPattern,
                        $dbPartition
                    );
            }

            /** @var RangeDbPartitionSettings $additional */
            $additional = $dbPartition->getAdditional();
            if ($additional?->getStrategy()->isRange()) {
                $complexPattern = $rootTablePattern
                    .$dbPartition->getTableSuffixPattern()
                    .$additional->getTableSuffixPattern();

                $regexPattern = DbTableHelper::buildRegexTablePattern($complexPattern);
                $created[$complexPattern]['items'] =
                    PartitionService::createNextRangePartitionsByTableRegexPattern(
                        $additionalCount,
                        $regexPattern,
                        $additional
                    );
            }
        }

        return $created;
    }

    /**
     * @param  class-string<ModelPartitionedInterface>  $modelClass
     * @param  callable  $blueprintFn
     */
    public static function modifyTablesBySettings(string $modelClass, callable $blueprintFn): void
    {
        static::validatePartitionedModelClass($modelClass);
        $partitionSettings = $modelClass::getPartitionedSettings()->getAll();

        /** @var BasePartitionSettings $partitionSetting */
        foreach ($partitionSettings as $partitionSetting) {
            if ($partitionSetting instanceof BaseTablePartitionSettings) {
                $tables[] = $partitionSetting->getBaseTablePattern();
            } else {
                $tables = DbPartitionHelper::getAllRootTablesByPattern(
                    $partitionSetting->getBaseTablePattern()
                )->toArray();
            }

            foreach ($tables as $table) {
                Schema::table($table, $blueprintFn);
            }
        }
    }

    /**
     * @param  class-string<ModelPartitionedInterface>  $modelClass
     * @param  callable  $blueprintFn
     * @return CreatedTableDTO[]
     */
    public static function createTablesBySettings(string $modelClass, callable $blueprintFn): array
    {
        static::validatePartitionedModelClass($modelClass);

        $partitionSettings = $modelClass::getPartitionedSettings()->getAll();

        $createdTables = [];

        /** @var BasePartitionSettings $partitionSetting */
        foreach ($partitionSettings as $partitionSetting) {
            $strategy = $partitionSetting::getPartitionStrategyEnum();
            $need = $strategy->needToPreCreateTables();

            if ($need === false) {
                continue;
            }

            $tables = [];

            if ($partitionSetting instanceof HashViaCodePartitionSettings) {
                $tables = $partitionSetting->getAllTablesNames();
            } elseif ($partitionSetting instanceof BaseTablePartitionSettings) {
                $tables[] = $partitionSetting->getBaseTablePattern();
            }

            foreach ($tables as $tableName) {
                /** @var HashDbPartitionSettings|RangeDbPartitionSettings $dbPartition */
                if ($dbPartition = $partitionSetting->getDbPartition()) {
                    $createdTables[$strategy->value] = PartitionService::createRootPartitionedTable(
                        $tableName,
                        $dbPartition,
                        $blueprintFn
                    );
                } else {
                    PartitionSchema::create($tableName, $blueprintFn);
                    $createdTables[$strategy->value] = new CreatedTableDTO($tableName);
                }
            }
        }

        return $createdTables;
    }
}
