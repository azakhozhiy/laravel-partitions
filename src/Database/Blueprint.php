<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Database;

use Illuminate\Database\Schema\Blueprint as IlluminateBlueprint;
use Illuminate\Support\Fluent;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionDbStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeValueTypeEnum;

class Blueprint extends IlluminateBlueprint
{
    /**
     * Partition range key for creating a range partitioned table.
     *
     * @var string
     */
    public string $partitionColumn;

    /**
     * Partition range key for creating a range partitioned table.
     *
     * @var string
     */
    public string $partitionSuffix;

    /**
     * Column key for creating a table with list partition.
     *
     * @var string
     */
    public string $partitionTableName;

    /**
     * Partition range key for creating a range partitioned table.
     *
     * @var string
     */
    public string $rangeStart;

    /**
     * Partition range key for creating a range partitioned table.
     *
     * @var string
     */
    public string $rangeEnd;

    public ?PartitionRangeValueTypeEnum $rangeValueType = null;

    /**
     * Hashing modulus for creating a hash partition.
     *
     * @var int
     */
    public int $hashModulus;

    /**
     * Hashing reminder for creating a hash partition.
     *
     * @var int
     */
    public int $hashRemainder;

    public ?PartitionDbStrategyEnum $additionalPartitionType = null;
    public ?string $additionalPartitionColumn = null;

    public array $partitionCompositeKeys = [];
    public array $listPartitionValues = [];

    /**
     * List of commands that trigger the creating function.
     *
     * @var array
     */
    private array $creators = [
        'create',
        'createRangePartitioned',
        'createListPartitioned',
        'createHashPartitioned',
    ];

    public function hasCompositeKeys(): bool
    {
        return !empty($this->partitionCompositeKeys);
    }

    public function hasAdditionalPartition(): bool
    {
        return $this->additionalPartitionColumn && $this->additionalPartitionType;
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    public function creating(): bool
    {
        return collect($this->commands)->contains(fn ($command) => in_array($command->name, $this->creators, false));
    }

    /**
     * Indicate that the table needs to be created with a range partition.
     *
     * @return Fluent
     */
    public function createRangePartitioned(): Fluent
    {
        return $this->addCommand('createRangePartitioned');
    }

    /**
     * Create a range partition and attach it to the partitioned table.
     *
     * @return Fluent
     */
    public function createRangePartition(): Fluent
    {
        return $this->addCommand('createRangePartition');
    }

    /**
     * Attach a range partition for existing table to the partitioned table.
     *
     * @return Fluent
     */
    public function attachRangePartition(): Fluent
    {
        return $this->addCommand('attachRangePartition');
    }

    /**
     * Indicate that the table needs to be created with a list partition.
     *
     * @return Fluent
     */
    public function createListPartitioned(): Fluent
    {
        return $this->addCommand('createListPartitioned');
    }

    /**
     * Attach a range partition for existing table to the partitioned table.
     *
     * @return Fluent
     */
    public function attachListPartition(): Fluent
    {
        return $this->addCommand('attachListPartition');
    }

    /**
     * Create a list partition and attach it to the partitioned table.
     *
     * @return Fluent
     */
    public function createListPartition(): Fluent
    {
        return $this->addCommand('createListPartition');
    }

    /**
     * Indicate that the table needs to be created with a hash partition.
     *
     * @return Fluent
     */
    public function createHashPartitioned(): Fluent
    {
        return $this->addCommand('createHashPartitioned');
    }

    /**
     * Create a hash partition and attach it to the partitioned table.
     *
     * @return Fluent
     */
    public function createHashPartition(): Fluent
    {
        return $this->addCommand('createHashPartition');
    }

    /**
     * Attach a range partition for existing table to the partitioned table.
     *
     * @return Fluent
     */
    public function attachHashPartition(): Fluent
    {
        return $this->addCommand('attachHashPartition');
    }

    /**
     * Create a hash partition and attach it to the partitioned table.
     *
     * @return Fluent
     */
    public function getAllRangePartitionedTables(): Fluent
    {
        return $this->addCommand('getAllRangePartitionedTables');
    }

    /**
     * Create a hash partition and attach it to the partitioned table.
     *
     * @return Fluent
     */
    public function getAllHashPartitionedTables(): Fluent
    {
        return $this->addCommand('getAllHashPartitionedTables');
    }

    /**
     * Create a hash partition and attach it to the partitioned table.
     *
     * @return Fluent
     */
    public function getAllListPartitionedTables(): Fluent
    {
        return $this->addCommand('getAllListPartitionedTables');
    }

    /**
     * Create a hash partition and attach it to the partitioned table.
     *
     * @return Fluent
     */
    public function getPartitions(): Fluent
    {
        return $this->addCommand('getPartitions');
    }

    /**
     * Indicate that the table needs to be created with a range partition.
     *
     * @return Fluent
     */
    public function detachPartition(): Fluent
    {
        return $this->addCommand('detachPartition');
    }

}
