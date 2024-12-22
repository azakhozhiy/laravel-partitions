<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\DTO;

/**
 * @property-read CreatedTableDTO[] $dbPartitions
 */
class CreatedTableDTO
{
    public function __construct(
        protected string $tableName,
        protected array $compositeKeys = [],
        protected array $dbPartitions = []
    ) {
    }

    public function setDbPartitions(array $items): static
    {
        $this->dbPartitions = $items;

        return $this;
    }

    public function getCompositeKeys(): array
    {
        return $this->compositeKeys;
    }

    public function hasDbPartitions(): bool
    {
        return !empty($this->dbPartitions);
    }

    /**
     * @return CreatedTableDTO[]
     */
    public function getDbPartitions(): array
    {
        return $this->dbPartitions;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
