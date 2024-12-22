<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings\Database;

use Illuminate\Support\Arr;

class HashDbPartitionSettings extends BaseDbPartitionSettings
{
    public const string FIELD_COUNT = 'count';
    public const string FIELD_HASH_FUNCTION = 'hash_function';

    protected int $count;
    protected ?string $hashFunction = null;

    public static function createByConfig(array $config): static
    {
        $obj = (new static())
            ->fillBaseSettings($config);

        $obj->count = (int)Arr::get($config, static::FIELD_COUNT);
        $obj->hashFunction = Arr::get($config, static::FIELD_HASH_FUNCTION);

        return $obj;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getHashFunction(): ?string
    {
        return $this->hashFunction;
    }
}
