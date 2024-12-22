<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings\Database;

use Illuminate\Support\Arr;

class ListDbPartitionSettings extends BaseDbPartitionSettings
{
    public const string FIELD_LIST_VALUES = 'list_values';

    /**
     * example: [ 'processing' => ['active', 'pending'], 'dispute' => ['dispute'], 'new' => ['new']], count = 3
     * @var array[]
     */
    protected array $listValues = [];

    public function getListValues(): array
    {
        return $this->listValues;
    }

    public function getListLength(): int
    {
        return count($this->listValues);
    }

    public static function createByConfig(array $config): static
    {
        $obj = (new static())
            ->fillBaseSettings($config);

        $obj->listValues = (array)Arr::get($config, static::FIELD_LIST_VALUES, []);

        return $obj;
    }
}
