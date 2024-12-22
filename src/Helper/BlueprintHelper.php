<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Helper;

use AZakhozhiy\Laravel\Partitions\Database\Blueprint;
use AZakhozhiy\Laravel\Partitions\Enum\DbIndexConstraintEnum;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;

class BlueprintHelper
{
    public static function createIndex(
        DbIndexConstraintEnum $constraintEnum,
        Blueprint $blueprint,
        array|string $columns,
        ?string $name = null,
        ?string $algo = null
    ): void {
        match ($constraintEnum) {
            DbIndexConstraintEnum::UNIQUE => $blueprint->unique($columns, $name, $algo),
            DbIndexConstraintEnum::PRIMARY => $blueprint->primary($columns, $name, $algo),
            DbIndexConstraintEnum::FULLTEXT => $blueprint->fullText($columns, $name, $algo),
            DbIndexConstraintEnum::SPATIAL => $blueprint->spatialIndex($columns, $name),
            default => $blueprint->index($columns, $name, $algo)
        };
    }

    public static function buildCreateIndexesFn(array $indexes): callable
    {
        if (isset($indexes[0])) {
            $createIndexes = static function (Blueprint $blueprint) use ($indexes): void {
                /** @var array $indexData */
                foreach ($indexes as $indexData) {
                    $isset = isset(
                        $indexData[BaseDbPartitionSettings::FIELD_INDEX_COLUMNS],
                        $indexData[BaseDbPartitionSettings::FIELD_INDEX_CONSTRAINT]
                    );

                    if ($isset) {
                        $constraintEnum = DbIndexConstraintEnum::tryFrom(
                            $indexData[BaseDbPartitionSettings::FIELD_INDEX_CONSTRAINT]
                        );

                        $indexName = $indexData[BaseDbPartitionSettings::FIELD_INDEX_NAME] ?? null;
                        $indexAlgo = $indexData[BaseDbPartitionSettings::FIELD_INDEX_ALGO] ?? null;

                        if ($constraintEnum) {
                            static::createIndex(
                                $constraintEnum,
                                $blueprint,
                                $indexData['columns'],
                                $indexName,
                                $indexAlgo
                            );
                        }
                    }
                }
            };
        } else {
            $createIndexes = static fn (Blueprint $fn) => $fn;
        }

        return $createIndexes;
    }
}
