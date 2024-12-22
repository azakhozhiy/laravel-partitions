<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Facade;

use Closure;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Database\Builder;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionDbStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeValueTypeEnum;

/**
 *  Range partitioning related methods.
 * @method static createRangePartitioned(string $table, Closure $callback, string $partitionColumn, array $compositeKeys)
 * @method static createRangePartition(string $table, Closure $callback, string $partitionSuffix, string $rangeStart, string $rangeEnd, PartitionRangeValueTypeEnum $rangeValueType, PartitionDbStrategyEnum|null $additionalPartitionType = null, string|null $additionalPartitionColumn = null)
 * @method static attachRangePartition(string $table, Closure $callback, string $partitionTableName, string $rangeStart, string $rangeEnd, PartitionRangeValueTypeEnum $rangeValueType)
 *
 *  List partitioning related methods.
 * @method static createListPartitioned(string $table, Closure $callback, string $partitionColumn, array $compositeKeys)
 * @method static createListPartition(string $table, Closure $callback, string $partitionSuffix, array $listPartitionValues, ?PartitionDbStrategyEnum $additionalPartitionType = null, ?string $additionalPartitionColumn = null)
 * @method static attachListPartition(string $table, Closure $callback, string $partitionTableName, array $listPartitionValues)
 *
 *  Hash partitioning related methods.
 * @method static createHashPartitioned(string $table, Closure $callback, string $partitionColumn, array $compositeKeys)
 * @method static createHashPartition(string $table, Closure $callback, string $partitionSuffix, int $hashModulus, int $hashRemainder, ?PartitionDbStrategyEnum $additionalPartitionType = null, ?string $additionalPartitionColumn = null)
 * @method static attachHashPartition(string $table, Closure $callback, string $partitionTableName, int $hashModulus, int $hashRemainder)
 *
 *  General partitioning methods.
 * @method static getPartitions(string $table)
 * @method static detachPartition(string $table, Closure $callback, string $partitionTableName)
 *
 * Helpers
 * @method static getAllRangePartitionedTables()
 * @method static getAllHashPartitionedTables()
 * @method static getAllListPartitionedTables()
 *
 * @see Builder
 */
class PartitionSchema extends Schema
{
    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     *
     * @throws RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = parent::getFacadeRoot();

        if (!$instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return (new Builder(static::$app['db']->connection()))->$method(...$args);
    }
}
