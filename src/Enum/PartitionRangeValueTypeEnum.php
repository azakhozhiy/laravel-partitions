<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Enum;

enum PartitionRangeValueTypeEnum: string
{
    case STRING = 'STRING';
    case INTEGER = 'INTEGER';
    case TIMESTAMP = 'TIMESTAMP';

    public function prepareValue(mixed $value): string|int
    {
        return match ($this) {
            self::STRING, self::TIMESTAMP => (string)$value,
            self::INTEGER => (int)$value,
        };
    }

    public function isString(): bool
    {
        return $this === self::STRING
            || $this === self::TIMESTAMP;
    }

    public function isInteger(): bool
    {
        return $this === self::INTEGER;
    }
}
