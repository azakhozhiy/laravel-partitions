<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Partition;

use EloquentFilter\Filterable;
use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Filter\BasePartitionFilter;

/**
 * @mixin Model|ModelPartitionedInterface
 */
trait PartitionFilterable
{
    use Filterable;
    use ModelPartitioned;

    public static function partitionFilter(
        PartitionStrategyEnum $strategyEnum,
        string $partitionKey,
        array $input = [],
        string $filter = null
    ): Builder {
        /** @var ModelPartitionedInterface|Filterable $instance */
        $instance = new static();
        $instanceQuery = $instance::queryByPartitionKey($strategyEnum, $partitionKey);

        // Resolve the current Model's filter
        if ($filter === null) {
            try {
                $filter = $instance->getModelFilterClass();
            } catch (Throwable) {
                $filter = BasePartitionFilter::class;
            }
        }

        // Create the model filter instance
        /** @var ModelFilter $modelFilter */
        $modelFilter = new $filter($instanceQuery, $input);

        // Set the input that was used in the filter (this will exclude empty strings)
        $instance->filtered = (array)$modelFilter->input();

        // Return the filter query
        return $modelFilter->handle();
    }
}
