<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Partition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use AZakhozhiy\Laravel\Partitions\Contract\ManagesPartitionKeysInterface;

/**
 * @property array $partition_keys
 * @property int $last_used_partition_key_index
 *
 * @mixin Model
 * @mixin ManagesPartitionKeysInterface
 */
trait ManagesPartitionKeys
{
    public const string PARTITION_KEYS = 'partition_keys';
    public const string LAST_USED_PARTITION_KEY_INDEX = 'last_used_partition_key_index';

    protected const array PARTITION_KEYS_CASTS = [
        self::PARTITION_KEYS => 'json',
    ];

    public static function wasPartitionedByIdOnly(): bool
    {
        return false;
    }

    public static function getPartitionKeysColumn(): string
    {
        return static::PARTITION_KEYS;
    }

    public function getRawPartitionKeys(): array
    {
        if (!array_key_exists(static::PARTITION_KEYS, $this->attributes)) {
            return [];
        }

        if (static::wasPartitionedByIdOnly()) {
            return [null, ...$this->getAttribute(static::getPartitionKeysColumn())];
        }

        return $this->getAttribute(static::getPartitionKeysColumn());
    }

    public function addPartitionKeys(int $count = 1): static
    {
        $partitionKeys = $this->getRawPartitionKeys();

        for ($i = 0; $i < $count; $i++) {
            do {
                $newKey = Str::random(6);
            } while (in_array($newKey, $partitionKeys, true));

            $partitionKeys[] = $newKey;
        }

        $this->{static::getPartitionKeysColumn()} = $partitionKeys;

        return $this;
    }

    public function getPreparedPartitionKeys(): array
    {
        $partitionKeys = $this->getRawPartitionKeys();

        foreach ($partitionKeys as $i => $value) {
            $partitionKeys[$i] = is_null($value)
                ? $this->getKey()
                : $this->preparePartitionKey($value);
        }

        return $partitionKeys;
    }

    public function preparePartitionKey(string $key): string
    {
        return $this->getKey().'-'.$key;
    }

    public function getLastUsedPartitionKeyIndex(): int
    {
        return $this->{static::LAST_USED_PARTITION_KEY_INDEX};
    }

    public function getLastUsedPartitionKeyIndexColumn(): string
    {
        return static::LAST_USED_PARTITION_KEY_INDEX;
    }

    public function getAvailablePartitionKeys(): array
    {
        $partitionKeys = $this->getRawPartitionKeys();

        if (empty($partitionKeys)) {
            return [];
        }

        foreach ($partitionKeys as $i => $partitionKey) {
            if ($partitionKey === null) {
                unset($partitionKeys[$i]);
            }
        }

        return $partitionKeys;
    }

    public function getNextPartitionKey(): string
    {
        $partitionKeys = $this->getAvailablePartitionKeys();

        $partitionKeysLength = count($partitionKeys);

        if ($partitionKeysLength === 0) {
            return $this->getKey();
        }

        if ($partitionKeysLength === 1) {
            return $this->preparePartitionKey($partitionKeys[0]);
        }

        if ($this->getLastUsedPartitionKeyIndex() >= $partitionKeysLength) {
            $this->{$this->getLastUsedPartitionKeyIndexColumn()} = 0;
        }

        $lastUsedIndex = ($this->getLastUsedPartitionKeyIndex() + 1) % $partitionKeysLength;

        $this->{$this->getLastUsedPartitionKeyIndexColumn()} = $lastUsedIndex;
        $this->save();

        $partitionKey = $partitionKeys[$lastUsedIndex] ?? $partitionKeys[0];

        return $this->preparePartitionKey($partitionKey);
    }

    protected function getLastUsedPartitionKeyIndexCacheKey(): string
    {
        $prefix = Str::snake(Str::pluralStudly(class_basename($this)));

        return "{$prefix}_last_used_index_".$this->getKey();
    }

    public function syncLastUsedPartitionIndex(): void
    {
        $cacheKey = $this->getLastUsedPartitionKeyIndexCacheKey();

        $lastUsedIndex = Cache::get($cacheKey);

        static::query()
            ->where('id', $this->getKey())
            ->update([static::LAST_USED_PARTITION_KEY_INDEX => $lastUsedIndex]);
    }

    protected function initializePartitionIndex(): void
    {
        $cacheKey = $this->getLastUsedPartitionKeyIndexCacheKey();

        $item = static::query()
            ->where('id', $this->getKey())
            ->first();

        $lastUsedIndex = $item->{static::LAST_USED_PARTITION_KEY_INDEX} ?? 0;

        Cache::put($cacheKey, $lastUsedIndex);
    }


}
