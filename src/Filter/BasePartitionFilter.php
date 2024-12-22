<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Filter;

use EloquentFilter\ModelFilter;

abstract class BasePartitionFilter extends ModelFilter
{
    public function hasSelectColumns(): bool
    {
        return isset($this->getSelectColumns()[0]);
    }

    public function getSelectColumns(): array
    {
        return [];
    }
}
