<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Helper;

use lastguest\Murmur;

class PartitionHashHelper
{
    public static function getTableNameByNumber(int $number, string $pattern): string
    {
        return sprintf($pattern, $number);
    }

    public static function getPartitionNumberByKey(string $key, int $count): int
    {
        $hash = Murmur::hash3_int($key);

        return abs($hash) % $count;
    }
}
