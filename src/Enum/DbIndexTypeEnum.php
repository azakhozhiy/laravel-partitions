<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Enum;

enum DbIndexTypeEnum: string
{
    case BTREE = 'BTREE';
    case HASH = 'HASH';
    case BRIN = 'BRIN';
    case EXPRESSION = 'EXPRESSION';
}
