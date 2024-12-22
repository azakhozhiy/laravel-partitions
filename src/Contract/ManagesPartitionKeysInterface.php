<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Contract;

interface ManagesPartitionKeysInterface
{
    public const string PARTITION_KEYS = 'partition_keys';
    public const string LAST_USED_PARTITION_KEY_INDEX = 'last_used_partition_key_index';

    public static function wasPartitionedByIdOnly(): bool;

    public static function getPartitionKeysColumn(): string;

    public function addPartitionKeys(int $count = 1): static;

    public function getRawPartitionKeys(): array;

    public function getAvailablePartitionKeys(): array;

    public function getPreparedPartitionKeys(): array;

    public function getNextPartitionKey(): string;

    public function getLastUsedPartitionKeyIndex(): int;

    public function getLastUsedPartitionKeyIndexColumn(): string;
}
