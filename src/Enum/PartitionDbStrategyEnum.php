<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Enum;

enum PartitionDbStrategyEnum: string
{
    case HASH = 'HASH';
    case RANGE = 'RANGE';
    case LIST = 'LIST';

    public function isRange(): bool
    {
        return $this === self::RANGE;
    }

    public function isHash(): bool
    {
        return $this === self::HASH;
    }

    public function isList(): bool
    {
        return $this === self::LIST;
    }
}
