<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\DTO;

class MorphKeys
{
    protected array $foreignKeys;

    public function __construct(protected string $type, protected string $relationName)
    {
    }

    public function getRelationName(): string
    {
        return $this->relationName;
    }

    public function setRelationName(string $relationName): static
    {
        $this->relationName = $relationName;

        return $this;
    }

    public function isCompositePrimaryKey(): bool
    {
        return count($this->foreignKeys) > 1;
    }

    public function setForeignKeys(array $foreignKey): static
    {
        $this->foreignKeys = $foreignKey;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasForeignKeys(): bool
    {
        return !empty($this->foreignKeys);
    }

    public function getFirstForeignKey(): ?string
    {
        return $this->foreignKeys[0] ?? null;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function addForeignKey(string $key): static
    {
        $this->foreignKeys[] = $key;

        return $this;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
