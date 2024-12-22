<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Database;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as BasePostgresGrammar;
use Illuminate\Support\Fluent;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeValueTypeEnum;

class PostgresGrammar extends BasePostgresGrammar
{
    /**
     * Compile a create table command with its range partitions.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return array
     */
    public function compileCreateRangePartitioned(Blueprint $blueprint, Fluent $command): array
    {
        return array_values(array_filter(array_merge([
            sprintf(
                'create table %s (%s) partition by range (%s)',
                $this->wrapTable($blueprint),
                $this->buildTableStructure($blueprint),
                $blueprint->partitionColumn
            ),
        ], [$this->compileAutoIncrementStartingValues($blueprint, $command)])));
    }

    public function compileAutoIncrementStartingValues(BaseBlueprint $blueprint, Fluent $command)
    {
        if ($command->column?->autoIncrement
            && $value = $command->column?->get('startingValue', $command->column?->get('from'))) {
            return 'alter sequence '.$blueprint->getTable().'_'.$command->column?->name.'_seq restart with '.$value;
        }
    }

    private function buildTableStructure(Blueprint $blueprint): string
    {
        if ($blueprint->hasCompositeKeys()) {
            $createTable = sprintf(
                '%s, %s',
                implode(', ', $this->getColumns($blueprint)),
                static::buildPrimaryKey($blueprint->partitionCompositeKeys)
            );
        } else {
            $createTable = sprintf(
                '%s',
                implode(', ', $this->getColumns($blueprint)),
            );
        }

        return $createTable;
    }

    public function compileCreateHashPartitioned(Blueprint $blueprint, Fluent $command): array
    {
        return array_values(array_filter(array_merge([
            sprintf(
                'create table %s (%s) partition by hash(%s)',
                $this->wrapTable($blueprint),
                $this->buildTableStructure($blueprint),
                $blueprint->partitionColumn
            ),
        ], [$this->compileAutoIncrementStartingValues($blueprint, $command)])));
    }

    /**
     * Compile a create table command with its list partitions.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return string
     */
    public function compileCreateListPartitioned(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf(
            'create table %s (%s) partition by list(%s)',
            $this->wrapTable($blueprint),
            $this->buildTableStructure($blueprint),
            $blueprint->partitionColumn
        );
    }

    /**
     * Compile a create table partition command for a hash partitioned table.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return string
     */
    public function compileCreateHashPartition(Blueprint $blueprint, Fluent $command): string
    {
        if ($blueprint->hasAdditionalPartition()) {
            $format = 'create table %s_%s partition of %s for values with (modulus %s, remainder %s) partition by %s (%s)';

            return sprintf(
                $format,
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $blueprint->partitionSuffix,
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $blueprint->hashModulus,
                $blueprint->hashRemainder,
                $blueprint->additionalPartitionType->value,
                $blueprint->additionalPartitionColumn
            );
        }

        $format = 'create table %s_%s partition of %s for values with (modulus %s, remainder %s)';

        return sprintf(
            $format,
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $blueprint->partitionSuffix,
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $blueprint->hashModulus,
            $blueprint->hashRemainder
        );
    }

    /**
     * Compile a create table partition command for a range partitioned table.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return array
     */
    public function compileCreateRangePartition(Blueprint $blueprint, Fluent $command): array
    {
        $createTable = null;
        $rangeStart = $blueprint->rangeStart;
        $rangeEnd = $blueprint->rangeEnd;
        $createTableFormat = 'create table %s_%s partition of %s for values from (\'%s\') to (\'%s\')';

        if ($blueprint->rangeValueType) {
            $rangeStart = $blueprint->rangeValueType->prepareValue($rangeStart);
            $rangeEnd = $blueprint->rangeValueType->prepareValue($rangeEnd);

            $createTableFormat = $blueprint->rangeValueType === PartitionRangeValueTypeEnum::INTEGER
                ? 'create table %s_%s partition of %s for values from (%d) to (%d)'
                : 'create table %s_%s partition of %s for values from (\'%s\') to (\'%s\')';
        }

        if ($blueprint->hasAdditionalPartition()) {
            $createTableFormat = $blueprint->rangeValueType === PartitionRangeValueTypeEnum::INTEGER
                ? 'create table %s_%s partition of %s for values from (%d) to (%d) partition by %s (%s)'
                : 'create table %s_%s partition of %s for values from (\'%s\') to (\'%s\') partition by %s (%s)';

            $createTable = sprintf(
                $createTableFormat,
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $blueprint->partitionSuffix,
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $rangeStart,
                $rangeEnd,
                $blueprint->additionalPartitionType->value,
                $blueprint->additionalPartitionColumn
            );
        }

        if ($createTable === null) {
            $createTable = sprintf(
                $createTableFormat,
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $blueprint->partitionSuffix,
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $rangeStart,
                $rangeEnd
            );
        }

        return array_values(
            array_filter(
                array_merge(
                    [$createTable],
                    [$this->compileAutoIncrementStartingValues($blueprint, $command)]
                )
            )
        );
    }

    /**
     * Compile a create table partition command for a list partitioned table.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return string
     */
    public function compileCreateListPartition(Blueprint $blueprint, Fluent $command): string
    {
        $values = implode(", ", array_map(static fn ($value) => "'".$value."'", $blueprint->listPartitionValues));

        $createTable = null;

        if ($blueprint->hasAdditionalPartition()) {
            $createTable = sprintf(
                'create table %s_%s partition of %s for values in (%s) partition by %s (%s)',
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $blueprint->partitionSuffix,
                str_replace("\"", "", $this->wrapTable($blueprint)),
                $values,
                $blueprint->additionalPartitionType->value,
                $blueprint->additionalPartitionColumn
            );
        }

        return $createTable ?: sprintf(
            'create table %s_%s partition of %s for values in (%s)',
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $blueprint->partitionSuffix,
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $values,
        );
    }

    /**
     * Compile an attach partition command for a range partitioned table.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return string
     */
    public function compileAttachRangePartition(Blueprint $blueprint, Fluent $command): string
    {
        $rangeStart = $blueprint->rangeStart;
        $rangeEnd = $blueprint->rangeEnd;
        $createTableFormat = 'ALTER table %s attach partition %s for values from (\'%s\') to (\'%s\')';

        if ($blueprint->rangeValueType) {
            $rangeStart = $blueprint->rangeValueType->prepareValue($rangeStart);
            $rangeEnd = $blueprint->rangeValueType->prepareValue($rangeEnd);

            $createTableFormat = $blueprint->rangeValueType === PartitionRangeValueTypeEnum::INTEGER
                ? 'ALTER table %s attach partition %s for values from (%d) to (%d)'
                : 'ALTER table %s attach partition %s for values from (\'%s\') to (\'%s\')';
        }

        return sprintf(
            $createTableFormat,
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $blueprint->partitionTableName,
            $rangeStart,
            $rangeEnd
        );
    }

    /**
     * Compile an attach partition command for a list partitioned table.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return string
     */
    public function compileAttachListPartition(Blueprint $blueprint, Fluent $command): string
    {
        $values = implode(", ", array_map(static fn ($value) => "'".$value."'", $blueprint->listPartitionValues));

        return sprintf(
            'alter table %s partition of %s for values in (%s)',
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $blueprint->partitionTableName,
            $values,
        );
    }

    /**
     * Compile a create table command with its hash partitions.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return array
     */

    /**
     * Compile an attach partition command for a hash partitioned table.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return string
     */
    public function compileAttachHashPartition(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf(
            'alter table %s partition of %s for values with (modulus %s, remainder %s)',
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $blueprint->partitionTableName,
            $blueprint->hashModulus,
            $blueprint->hashRemainder,
        );
    }

    /**
     * Get a list of all partitioned tables in the Database.
     * @param  string  $table
     * @return string
     */
    public function compileGetPartitions(string $table): string
    {
        return sprintf(
            "SELECT inhrelid::regclass as tables
            FROM   pg_catalog.pg_inherits
            WHERE  inhparent = '%s'::regclass;",
            $table,
        );
    }

    /**
     * Get all range partitioned tables.
     * @return string
     */
    public function compileGetAllRangePartitionedTables(): string
    {
        return "select pg_class.relname as tables from pg_class inner join pg_partitioned_table on pg_class.oid = pg_partitioned_table.partrelid where pg_partitioned_table.partstrat = 'r';";
    }

    /**
     * Get all list partitioned tables.
     * @return string
     */
    public function compileGetAllListPartitionedTables(): string
    {
        return "select pg_class.relname as tables from pg_class inner join pg_partitioned_table on pg_class.oid = pg_partitioned_table.partrelid where pg_partitioned_table.partstrat = 'l';";
    }

    /**
     * Get all hash partitioned tables.
     * @return string
     */
    public function compileGetAllHashPartitionedTables(): string
    {
        return "select pg_class.relname as tables from pg_class inner join pg_partitioned_table on pg_class.oid = pg_partitioned_table.partrelid where pg_partitioned_table.partstrat = 'h';";
    }

    /**
     * Compile a detach query for a partitioned table.
     *
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @return string
     */
    public function compileDetachPartition(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf(
            'alter table %s detach partition %s',
            str_replace("\"", "", $this->wrapTable($blueprint)),
            $blueprint->partitionTableName
        );
    }

    private static function buildPrimaryKey(array $keys): string
    {
        return sprintf('PRIMARY KEY (%s)', implode(', ', $keys));
    }
}
