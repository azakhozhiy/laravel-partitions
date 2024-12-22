<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Composite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Contract\UseCompositePrimaryKeyInterface;

/**
 * @mixin Model
 * @mixin UseCompositePrimaryKeyInterface
 */
trait HasCompositePrimaryKey
{
    protected array $primaryKeys = ['id'];
    protected string $compositeKeyGlueType = 'or';

    public function setCompositeKeyGlueType(string $type): static
    {
        if ($type !== 'or' && $type !== 'and') {
            throw new RuntimeException("Only 'or' and 'and' are supported types for composite key glue.");
        }

        $this->compositeKeyGlueType = $type;

        return $this;
    }

    /**
     * Get the primary keys for the model.
     *
     * @return array<int,string>
     */
    public function getKeyNames(): array
    {
        return $this->primaryKeys;
    }

    public function setKeyNames($keys): static
    {
        $this->primaryKeys = $keys;

        return $this;
    }

    /**
     * Get the table qualified key names.
     *
     * @return array<int,string>
     */
    public function getQualifiedKeyNames(): array
    {
        return array_map(fn($keyName) => $this->qualifyColumn($keyName), $this->getKeyNames());
    }

    /**
     * Get the values of the model's primary keys.
     *
     * @return array<int,mixed>
     */
    public function getKeys(): array
    {
        return array_map(fn($keyName) => $this->getAttribute($keyName), $this->getKeyNames());
    }

    public function isCompositePrimaryKey(): bool
    {
        return count($this->primaryKeys) > 1;
    }

    /**
     * Get the default foreign key names for the model.
     *
     * @return array<int,string>
     */
    public function getForeignKeys(): array
    {
        return array_map(fn($keyName) => Str::snake(class_basename($this)).'_'.$keyName, $this->getKeyNames());
    }

    public function getCompositeKeyGlueType(): string
    {
        return $this->compositeKeyGlueType;
    }
}
