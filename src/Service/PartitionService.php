<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Service;

use Carbon\Carbon;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\DTO\CreatedPartitionDTO;
use AZakhozhiy\Laravel\Partitions\DTO\CreatedTableDTO;
use AZakhozhiy\Laravel\Partitions\Facade\PartitionSchema;
use AZakhozhiy\Laravel\Partitions\Helper\BlueprintHelper;
use AZakhozhiy\Laravel\Partitions\Helper\DbPartitionHelper;
use AZakhozhiy\Laravel\Partitions\Helper\DbTableHelper;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\HashDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\ListDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\RangeDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;

class PartitionService
{
    /**
     * @param  string  $rootTable
     * @param  BaseDbPartitionSettings  $dbPartition
     * @param  callable  $blueprintFn
     * @return CreatedTableDTO
     */
    public static function createRootPartitionedTable(
        string $rootTable,
        BaseDbPartitionSettings $dbPartition,
        callable $blueprintFn
    ): CreatedTableDTO {
        switch ($dbPartition::class) {
            case HashDbPartitionSettings::class:
                static::createHashPartitionedTable($rootTable, $dbPartition, $blueprintFn);
                break;
            case RangeDbPartitionSettings::class:
                static::createRangePartitionedTable($rootTable, $dbPartition, $blueprintFn);
                break;
            case ListDbPartitionSettings::class:
                static::createListPartitionedTable($rootTable, $dbPartition, $blueprintFn);
                break;
        }

        return new CreatedTableDTO(
            $rootTable,
            $dbPartition->getCompositeKeys(),
            static::createDbPartitions($rootTable, $dbPartition)
        );
    }

    public static function createHashPartitionedTable(
        string $rootTable,
        HashDbPartitionSettings $dbPartition,
        callable $partitionedFn
    ): void {
        // Create root hash partitioned table
        PartitionSchema::createHashPartitioned(
            $rootTable,
            $partitionedFn,
            $dbPartition->getColumn(),
            $dbPartition->getCompositeKeys()
        );
    }

    public static function createRangePartitionedTable(
        string $rootTable,
        RangeDbPartitionSettings $dbPartition,
        callable $partitionedFn
    ): void {
        // Create root range partitioned table
        PartitionSchema::createRangePartitioned(
            $rootTable,
            $partitionedFn,
            $dbPartition->getColumn(),
            $dbPartition->getCompositeKeys()
        );
    }

    public static function createListPartitionedTable(
        string $rootTable,
        ListDbPartitionSettings $dbPartition,
        callable $blueprintFn
    ): void {
        PartitionSchema::createListPartitioned(
            $rootTable,
            $blueprintFn,
            $dbPartition->getColumn(),
            $dbPartition->getCompositeKeys()
        );
    }


    /**
     * @param  string  $rootTable
     * @param  BaseDbPartitionSettings  $dbPartition
     * @param  bool  $createAdditional
     * @return CreatedPartitionDTO[]
     */
    public static function createDbPartitions(
        string $rootTable,
        BaseDbPartitionSettings $dbPartition,
        bool $createAdditional = true
    ): array {
        $count = 0;

        if ($dbPartition instanceof HashDbPartitionSettings) {
            $count = $dbPartition->getCount();
        }

        if ($dbPartition instanceof RangeDbPartitionSettings) {
            $count = $dbPartition->isAutoCreateTable()
                ? $dbPartition->getAutoCreateTableCount()
                : 0;
        }

        if ($dbPartition instanceof ListDbPartitionSettings) {
            $count = $dbPartition->getListLength();
            foreach ($dbPartition->getListValues() as $listName => $listValues) {
                $listData[] = ['name' => $listName, 'value' => $listValues];
            }
        }

        $baseRangeValue = null;
        $partitionInfo = null;
        $dbPartitions = [];

        for ($i = 0; $i < $count; $i++) {
            // Create hash db partitions
            if ($dbPartition instanceof HashDbPartitionSettings) {
                $partitionInfo = static::createDbHashPartition($rootTable, $dbPartition, $i, $createAdditional);
            }

            // Create range db partitions
            if ($dbPartition instanceof RangeDbPartitionSettings) {
                $partitionInfo = static::createDbRangePartition(
                    $rootTable,
                    $dbPartition,
                    $baseRangeValue,
                    $createAdditional
                );
                $baseRangeValue = $partitionInfo->getBaseValue();
            }

            // Create list db partitions
            if ($dbPartition instanceof ListDbPartitionSettings && isset($listData)) {
                $partitionInfo = static::createDbListPartition(
                    $rootTable,
                    $dbPartition,
                    $listData[$i]['name'],
                    $listData[$i]['value'],
                    $createAdditional
                );
            }

            $dbPartitions[] = $partitionInfo;
        }

        return $dbPartitions;
    }

    public static function createDbListPartition(
        string $rootTable,
        ListDbPartitionSettings $dbPartition,
        string $listName,
        array $values,
        bool $createAdditional = true
    ): CreatedPartitionDTO {
        // Build partition suffix
        $partitionSuffix = sprintf($dbPartition->getTableSuffixPattern(), $listName);

        // Create indexes closure
        $createLocalIndexesFn = BlueprintHelper::buildCreateIndexesFn($dbPartition->getLocalIndexes());

        // Check additional partition
        $additionalPartition = $createAdditional ? $dbPartition->getAdditional() : null;

        PartitionSchema::createListPartition(
            $rootTable,
            $createLocalIndexesFn,
            $partitionSuffix,
            $values,
            $additionalPartition?->getStrategy(),
            $additionalPartition?->getColumn()
        );

        $partitionTable = DbTableHelper::buildTableName($rootTable, $partitionSuffix);

        // Create additional partition
        $additionalDbPartitions = [];
        if ($createAdditional && $additionalPartition) {
            $additionalDbPartitions = static::createDbPartitions(
                $partitionTable,
                $additionalPartition,
                false
            );
        }

        return new CreatedPartitionDTO($partitionTable, null, $additionalDbPartitions);
    }

    public static function createDbHashPartition(
        string $rootTable,
        HashDbPartitionSettings $dbPartition,
        int $number,
        bool $createAdditional = true
    ): CreatedPartitionDTO {
        // Build partition suffix
        $partitionSuffix = sprintf($dbPartition->getTableSuffixPattern(), $number);

        // Create indexes closure
        $createLocalIndexesFn = BlueprintHelper::buildCreateIndexesFn($dbPartition->getLocalIndexes());

        // Check additional partition
        $additionalPartition = $createAdditional ? $dbPartition->getAdditional() : null;

        // Create hash partition
        PartitionSchema::createHashPartition(
            $rootTable,
            $createLocalIndexesFn,
            $partitionSuffix,
            $dbPartition->getCount(),
            $number,
            $additionalPartition?->getStrategy(),
            $additionalPartition?->getColumn()
        );

        $partitionTable = DbTableHelper::buildTableName($rootTable, $partitionSuffix);

        // Create additional partition
        $additionalDbPartitions = [];
        if ($createAdditional && $additionalPartition) {
            $additionalDbPartitions = static::createDbPartitions(
                $partitionTable,
                $additionalPartition,
                false
            );
        }

        return new CreatedPartitionDTO($partitionTable, null, $additionalDbPartitions);
    }

    public static function createDbRangePartition(
        string $rootTable,
        RangeDbPartitionSettings $dbPartition,
        Carbon|int|null $rangeBaseValue,
        bool $createAdditional = true
    ): CreatedPartitionDTO {
        // Calculate
        $rangeStep = $dbPartition->getRangeStep();
        $rangeInfo = $rangeStep->calculateRangeInfo($rangeBaseValue);

        // Build partition suffix
        $partitionSuffix = sprintf($dbPartition->getTableSuffixPattern(), $rangeInfo->getRangeSuffix());

        // Create indexes closure
        $createLocalIndexesFn = BlueprintHelper::buildCreateIndexesFn($dbPartition->getLocalIndexes());

        // Check additional partition
        $additionalPartition = $createAdditional ? $dbPartition->getAdditional() : null;

        // Create range partition with options
        PartitionSchema::createRangePartition(
            $rootTable,
            $createLocalIndexesFn,
            $partitionSuffix,
            $rangeInfo->getFormattedRangeStart(),
            $rangeInfo->getFormattedRangeEnd(),
            $dbPartition->getRangeType()->getRangeValueType(),
            $additionalPartition?->getStrategy(),
            $additionalPartition?->getColumn()
        );

        $partitionTable = DbTableHelper::buildTableName($rootTable, $partitionSuffix);
        $additionalDbPartitions = [];

        // Create additional
        if ($createAdditional && $additionalPartition) {
            $additionalDbPartitions = static::createDbPartitions(
                $partitionTable,
                $additionalPartition,
                false
            );
        }

        return new CreatedPartitionDTO($partitionTable, $rangeInfo->getRangeEnd(), $additionalDbPartitions);
    }

    public static function createNextRangePartitionsByTableRegexPattern(
        int $count,
        string $regexPattern,
        RangeDbPartitionSettings $dbPartitionSettings,
    ): array {
        $createdPartitions = [];
        $partitions = DbPartitionHelper::getAllPartitionsByRegexPattern($regexPattern);
        $rangeType = $dbPartitionSettings->getRangeType();
        $rangeValueType = $rangeType->getRangeValueType();

        foreach ($partitions as $partition) {
            $partitionTable = $partition['partition_table'];

            $lastRangePartition = DbPartitionHelper::getLastRangePartition($partitionTable, $rangeValueType);

            if ($lastRangePartition === null) {
                continue;
            }

            $rangeBaseValue = $rangeValueType->isInteger()
                ? (int)$lastRangePartition['range_to']
                : Carbon::parse($lastRangePartition['range_to']);

            for ($i = 0; $i < $count; $i++) {
                $childCreatedPartition = self::createDbRangePartition(
                    $partitionTable,
                    $dbPartitionSettings,
                    $rangeBaseValue,
                    false
                );

                $createdPartitions[$partitionTable][] = $childCreatedPartition;
                $rangeBaseValue = $childCreatedPartition->getBaseValue();
            }
        }

        return $createdPartitions;
    }

    /**
     * @param  class-string<ModelPartitionedInterface>  $modelClass
     * @param  array  $keys
     * @return array
     */
    public static function groupKeysByPartition(
        string $modelClass,
        HashViaCodePartitionSettings $settings,
        array $keys
    ): array {
        $partitionGroups = [];

        foreach ($keys as $keyId) {
            $partitionNumber = $settings->getPartitionNumberByKeys($keyId);
            $partitionGroups[$partitionNumber][] = $keyId;
        }

        return $partitionGroups;
    }
}
