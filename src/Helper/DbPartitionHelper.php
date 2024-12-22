<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Helper;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeValueTypeEnum;

class DbPartitionHelper
{
    public static function getAllRootTablesByPattern(string $pattern): Collection
    {
        return DB::table('pg_class as parent_table')
            ->leftJoin('pg_inherits', 'parent_table.oid', '=', 'pg_inherits.inhrelid')
            ->whereNull('pg_inherits.inhrelid')
            ->where('parent_table.relkind', '=', 'r')
            ->where('parent_table.relname', 'NOT LIKE', 'pg_%')
            ->where('parent_table.relname', 'NOT LIKE', 'sql_%')
            ->where('parent_table.relname', 'ILIKE', '%'.$pattern.'%')
            ->select(['parent_table.relname as root_table_name'])
            ->get()
            ->map(fn (array $item) => $item['root_table_name']);
    }

    public static function getAllPartitionsByRegexPattern(string $regexPattern): array
    {
        return DB::table('pg_inherits as i')
            ->join('pg_class as c', 'i.inhrelid', '=', 'c.oid')
            ->join('pg_class as p', 'i.inhparent', '=', 'p.oid')
            ->where('p.relname', '~', $regexPattern)
            ->select('c.relname as partition_table')
            ->get()
            ->toArray();
    }

    public static function getLastRangePartition(string $tableName, PartitionRangeValueTypeEnum $valueType): ?array
    {
        // Выполняем запрос для получения имени и диапазона значений (FROM и TO) для последней партиции
        $lastPartition = DB::table('pg_class as c')
            ->join('pg_inherits as i', 'c.oid', '=', 'i.inhrelid')
            ->where('i.inhparent', '=', DB::raw("'$tableName'::regclass"))
            ->orderBy(DB::raw("pg_get_expr(c.relpartbound, c.oid)"), 'desc')  // Сортируем по range FROM
            ->limit(1)
            ->select([
                'c.relname as partition_name',  // Имя последней партиции
                // Извлечение значения для range_from (в зависимости от типа данных)
                DB::raw(
                    $valueType->isString()
                        ? "regexp_replace(pg_get_expr(c.relpartbound, c.oid), '^.*FROM \\(\'([^\']+)\'\\) TO \\(\'([^\']+)\'\\).*$' , '\\1') as range_from"
                        : "regexp_replace(pg_get_expr(c.relpartbound, c.oid), '^.*FROM \\(([0-9]+)\\) TO \\(([0-9]+)\\).*$' , '\\1') as range_from"
                ),
                // Извлечение значения для range_to (в зависимости от типа данных)
                DB::raw(
                    $valueType->isInteger()
                        ? "regexp_replace(pg_get_expr(c.relpartbound, c.oid), '^.*FROM \\(\'([^\']+)\'\\) TO \\(\'([^\']+)\'\\).*$' , '\\2') as range_to"
                        : "regexp_replace(pg_get_expr(c.relpartbound, c.oid), '^.*FROM \\(([0-9]+)\\) TO \\(([0-9]+)\\).*$' , '\\2') as range_to"
                ),
            ])
            ->first();

        // Если нет партиции, возвращаем null
        if (!$lastPartition) {
            return null;
        }

        // Возвращаем диапазон FROM и TO последней партиции
        return [
            'partition_name' => $lastPartition->partition_name,
            'range_from' => $lastPartition->range_from,
            'range_to' => $lastPartition->range_to,
        ];
    }
}
