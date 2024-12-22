<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\DTO;

use Carbon\Carbon;

/**
 * @property Carbon|int|null $rangeBaseValue it used for determine next range / next value
 * @property CreatedPartitionDTO[] $childPartitions
 */
class CreatedPartitionDTO
{
    /**
     * @param  string  $tableName
     * @param  Carbon|int|null  $baseValue
     * @param  CreatedPartitionDTO[]  $childPartitions
     */
    public function __construct(
        protected string $tableName,
        protected Carbon|int|null $baseValue,
        protected array $childPartitions = []
    ) {
    }

    /**
     * @return CreatedPartitionDTO[]
     */
    public function getChildPartitions(): array
    {
        return $this->childPartitions;
    }

    public function addChildPartition(CreatedPartitionDTO $item): static
    {
        $this->childPartitions[] = $item;

        return $this;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getBaseValue(): int|Carbon|null
    {
        return $this->baseValue;
    }
}
