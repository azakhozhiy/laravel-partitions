<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Contract;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
interface UseCompositePrimaryKeyInterface
{
    public function getKeyNames(): array;

    public function setKeyNames($keys): static;

    public function getQualifiedKeyNames(): array;

    public function getKeys(): array;

    public function getForeignKeys(): array;

    public function getCompositeKeyGlueType(): string;

    public function isCompositePrimaryKey(): bool;

    public function setCompositeKeyGlueType(string $type): static;
}
