<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Partition;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Filter\BasePartitionFilter;

trait HasUnionPartitionQuery
{
    /**
     * @param  PartitionStrategyEnum  $strategyEnum
     * @param  class-string<ModelPartitionedInterface>  $modelPartitionedClass
     * @param  array  $partitionKeys
     * @param  array  $selectFields
     * @param  BasePartitionFilter|null  $dbFilter
     * @return Builder
     */
    public function getUnionDbQueryByPartitionKeys(
        PartitionStrategyEnum $strategyEnum,
        string $modelPartitionedClass,
        array $partitionKeys,
        array $selectFields = ['*'],
        ?BasePartitionFilter $dbFilter = null
    ): Builder {
        if (empty($partitionKeys)) {
            throw new InvalidArgumentException('The array of partition keys cannot be empty.');
        }

        if (!is_subclass_of($modelPartitionedClass, ModelPartitionedInterface::class)) {
            throw new InvalidArgumentException(
                "Model class should be implement ".ModelPartitionedInterface::class
            );
        }

        $firstQuery = null;
        $strategySettings = $modelPartitionedClass::getPartitionedSettings()->getByStrategy($strategyEnum);

        foreach ($partitionKeys as $key => $partitionKey) {
            $table = $strategySettings->getTableNameByPartitionKeys($partitionKey);
            $query = DB::table($table);

            if ($dbFilter && $dbFilter->hasSelectColumns()) {
                $query->select($dbFilter->getSelectColumns());
            }

            if ($dbFilter) {
                $query = $dbFilter->setQuery($query)->handle();
            }

            if ($key === 0) {
                $firstQuery = $query;
            } else {
                $firstQuery = $firstQuery->unionAll($query);
            }
        }

        return $firstQuery;
    }
}
