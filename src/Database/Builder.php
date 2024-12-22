<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Database;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder as BaseBuilder;
use Illuminate\Support\Facades\DB;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionDbStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeValueTypeEnum;

class Builder extends BaseBuilder
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->grammar = new PostgresGrammar();
    }

    /**
     * Create a table on the schema with hash partitions.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionColumn
     * @param  array  $compositeKeys
     * @return void
     * @throws BindingResolutionException
     */
    public function createHashPartitioned(
        string $table,
        Closure $callback,
        string $partitionColumn,
        array $compositeKeys
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use ($callback, $compositeKeys, $partitionColumn): void {
                    $blueprint->createHashPartitioned();
                    $blueprint->partitionCompositeKeys = $compositeKeys;
                    $blueprint->partitionColumn = $partitionColumn;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Create and attach a new hash partition on the table.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionSuffix
     * @param  int  $hashModulus
     * @param  int  $hashRemainder
     * @param  PartitionDbStrategyEnum|null  $additionalPartitionType
     * @param  string|null  $additionalPartitionColumn
     * @return void
     * @throws BindingResolutionException
     */
    public function createHashPartition(
        string $table,
        Closure $callback,
        string $partitionSuffix,
        int $hashModulus,
        int $hashRemainder,
        ?PartitionDbStrategyEnum $additionalPartitionType = null,
        ?string $additionalPartitionColumn = null
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use (
                    $callback,
                    $partitionSuffix,
                    $hashModulus,
                    $hashRemainder,
                    $additionalPartitionType,
                    $additionalPartitionColumn
                ): void {
                    $blueprint->createHashPartition();
                    $blueprint->partitionSuffix = $partitionSuffix;
                    $blueprint->hashModulus = $hashModulus;
                    $blueprint->hashRemainder = $hashRemainder;
                    $blueprint->additionalPartitionType = $additionalPartitionType;
                    $blueprint->additionalPartitionColumn = $additionalPartitionColumn;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Attach a hash partition.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionTableName
     * @param  int  $hashModulus
     * @param  int  $hashRemainder
     * @return void
     * @throws BindingResolutionException
     */
    public function attachHashPartition(
        string $table,
        Closure $callback,
        string $partitionTableName,
        int $hashModulus,
        int $hashRemainder
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use (
                    $callback,
                    $partitionTableName,
                    $hashModulus,
                    $hashRemainder
                ): void {
                    $blueprint->attachHashPartition();
                    $blueprint->partitionTableName = $partitionTableName;
                    $blueprint->hashModulus = $hashModulus;
                    $blueprint->hashRemainder = $hashRemainder;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Create a new table on the schema with range partitions.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionColumn
     * @param  array  $compositeKeys
     * @return void
     * @throws BindingResolutionException
     */
    public function createRangePartitioned(
        string $table,
        Closure $callback,
        string $partitionColumn,
        array $compositeKeys,
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use ($callback, $compositeKeys, $partitionColumn): void {
                    $blueprint->createRangePartitioned();
                    $blueprint->partitionCompositeKeys = $compositeKeys;
                    $blueprint->partitionColumn = $partitionColumn;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Create a new range partition on the table.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionSuffix
     * @param  string  $rangeStart
     * @param  string  $rangeEnd
     * @param  PartitionRangeValueTypeEnum  $rangeValueType
     * @param  PartitionDbStrategyEnum|null  $additionalPartitionType
     * @param  string|null  $additionalPartitionColumn
     * @return void
     * @throws BindingResolutionException
     */
    public function createRangePartition(
        string $table,
        Closure $callback,
        string $partitionSuffix,
        string $rangeStart,
        string $rangeEnd,
        PartitionRangeValueTypeEnum $rangeValueType,
        ?PartitionDbStrategyEnum $additionalPartitionType = null,
        ?string $additionalPartitionColumn = null
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use (
                    $callback,
                    $partitionSuffix,
                    $rangeValueType,
                    $rangeStart,
                    $rangeEnd,
                    $additionalPartitionType,
                    $additionalPartitionColumn
                ): void {
                    $blueprint->createRangePartition();
                    $blueprint->rangeValueType = $rangeValueType;
                    $blueprint->partitionSuffix = $partitionSuffix;
                    $blueprint->rangeStart = $rangeStart;
                    $blueprint->rangeEnd = $rangeEnd;
                    $blueprint->additionalPartitionType = $additionalPartitionType;
                    $blueprint->additionalPartitionColumn = $additionalPartitionColumn;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Attach a new range partition to a partitioned table.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionTableName
     * @param  string  $rangeStart
     * @param  string  $rangeEnd
     * @param  PartitionRangeValueTypeEnum  $rangeValueType
     * @return void
     * @throws BindingResolutionException
     */
    public function attachRangePartition(
        string $table,
        Closure $callback,
        string $partitionTableName,
        string $rangeStart,
        string $rangeEnd,
        PartitionRangeValueTypeEnum $rangeValueType,
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use (
                    $callback,
                    $partitionTableName,
                    $rangeStart,
                    $rangeEnd,
                    $rangeValueType
                ): void {
                    $blueprint->attachRangePartition();
                    $blueprint->partitionTableName = $partitionTableName;
                    $blueprint->rangeStart = $rangeStart;
                    $blueprint->rangeEnd = $rangeEnd;
                    $blueprint->rangeValueType = $rangeValueType;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Create a new table on the schema with list partitions.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionColumn
     * @param  array  $compositeKeys
     * @return void
     * @throws BindingResolutionException
     */
    public function createListPartitioned(
        string $table,
        Closure $callback,
        string $partitionColumn,
        array $compositeKeys,
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use (
                    $callback,
                    $compositeKeys,
                    $partitionColumn
                ): void {
                    $blueprint->createListPartitioned();
                    $blueprint->partitionCompositeKeys = $compositeKeys;
                    $blueprint->partitionColumn = $partitionColumn;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Create a list partition on the table.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionSuffix
     * @param  array  $listPartitionValues
     * @param  PartitionDbStrategyEnum|null  $additionalPartitionType
     * @param  string|null  $additionalPartitionColumn
     * @return void
     * @throws BindingResolutionException
     */
    public function createListPartition(
        string $table,
        Closure $callback,
        string $partitionSuffix,
        array $listPartitionValues,
        ?PartitionDbStrategyEnum $additionalPartitionType = null,
        ?string $additionalPartitionColumn = null
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use (
                    $callback,
                    $partitionSuffix,
                    $listPartitionValues,
                    $additionalPartitionType,
                    $additionalPartitionColumn
                ): void {
                    $blueprint->createListPartition();
                    $blueprint->partitionSuffix = $partitionSuffix;
                    $blueprint->listPartitionValues = $listPartitionValues;
                    $blueprint->additionalPartitionType = $additionalPartitionType;
                    $blueprint->additionalPartitionColumn = $additionalPartitionColumn;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Attach a new list partition.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionTableName
     * @param  array  $listPartitionValues
     * @return void
     * @throws BindingResolutionException
     */
    public function attachListPartition(
        string $table,
        Closure $callback,
        string $partitionTableName,
        array $listPartitionValues
    ): void {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use (
                    $callback,
                    $partitionTableName,
                    $listPartitionValues
                ): void {
                    $blueprint->attachListPartition();
                    $blueprint->partitionTableName = $partitionTableName;
                    $blueprint->listPartitionValues = $listPartitionValues;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Get all the partitioned table names for the database.
     *
     * @param  string  $table
     * @return array
     */
    public function getPartitions(string $table): array
    {
        return array_column(DB::select($this->grammar->compileGetPartitions($table)), 'tables');
    }

    /**
     * Get all the range partitioned table names for the database.
     *
     * @return array
     */
    public function getAllRangePartitionedTables(): array
    {
        return array_column(DB::select($this->grammar->compileGetAllRangePartitionedTables()), 'tables');
    }

    /**
     * Get all the list partitioned table names for the database.
     *
     * @return array
     */
    public function getAllListPartitionedTables(): array
    {
        return array_column(DB::select($this->grammar->compileGetAllListPartitionedTables()), 'tables');
    }

    /**
     * Get all the hash partitioned table names for the database.
     *
     * @return array
     */
    public function getAllHashPartitionedTables(): array
    {
        return array_column(DB::select($this->grammar->compileGetAllHashPartitionedTables()), 'tables');
    }

    /**
     * Detaches a partition from a partitioned table.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @param  string  $partitionTableName
     * @return void
     * @throws BindingResolutionException
     */
    public function detachPartition(string $table, Closure $callback, string $partitionTableName): void
    {
        $this->build(
            tap(
                $this->createBlueprint($table),
                static function (Blueprint $blueprint) use ($callback, $partitionTableName): void {
                    $blueprint->detachPartition();
                    $blueprint->partitionTableName = $partitionTableName;

                    $callback($blueprint);
                }
            )
        );
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param  string  $table
     * @param  Closure|null  $callback
     * @return Closure|mixed|object|Blueprint|null
     * @throws BindingResolutionException
     */
    protected function createBlueprint($table, Closure $callback = null): mixed
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback, $prefix);
        }

        return Container::getInstance()
            ->make(Blueprint::class, compact('table', 'callback', 'prefix'));
    }
}
