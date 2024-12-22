<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Enum;

enum DbIndexConstraintEnum: string
{
    case UNIQUE = 'unique';
    case PRIMARY = 'primary';
    case FULLTEXT = 'fulltext';
    case SPATIAL = 'spatial';
    case NONE = 'none';  // Обычный индекс, без ограничений
}
