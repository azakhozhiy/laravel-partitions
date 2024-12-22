<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings\Database;

use Illuminate\Support\Arr;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionDbStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Helper\DbPartitionHelper;

abstract class BaseDbPartitionSettings
{
    public const string FIELD_STRATEGY = 'strategy';
    public const string FIELD_TABLE_SUFFIX_PATTERN = 'table_suffix_pattern';
    public const string FIELD_COLUMN = 'column';
    public const string FIELD_ADDITIONAL = 'additional';
    public const string FIELD_COMPOSITE_KEYS = 'composite_keys';
    public const string FIELD_INDEXES = 'indexes';

    public const string FIELD_INDEX_NAME = 'name';
    public const string FIELD_INDEX_COLUMNS = 'columns';
    public const string FIELD_INDEX_ALGO = 'algo';
    public const string FIELD_INDEX_CONSTRAINT = 'constraint';

    protected PartitionDbStrategyEnum $strategy;
    protected string $column;
    protected string $tableSuffixPattern;
    protected array $compositeKeys = [];
    protected array $localIndexes = [];
    protected ?BaseDbPartitionSettings $additional = null;

    abstract public static function createByConfig(array $config): static;

    public function getCompositeKeys(): array
    {
        return $this->compositeKeys;
    }

    public function setAdditional(BaseDbPartitionSettings $dbPartitionSettings): static
    {
        $this->additional = $dbPartitionSettings;

        return $this;
    }

    public function hasLocalIndexes(): bool
    {
        return !empty($this->localIndexes);
    }

    public function getLocalIndexes(): array
    {
        return $this->localIndexes;
    }

    public function fillBaseSettings(array $config): static
    {
        $strategy = Arr::get($config, static::FIELD_STRATEGY);

        $strategyEnum = PartitionDbStrategyEnum::tryFrom($strategy);

        if (is_null($strategyEnum)) {
            throw new RuntimeException("Db partition strategy $strategy is not defined.");
        }

        $this->strategy = $strategyEnum;
        $this->column = Arr::get($config, static::FIELD_COLUMN);
        $this->tableSuffixPattern = Arr::get($config, static::FIELD_TABLE_SUFFIX_PATTERN);
        $this->compositeKeys = (array)Arr::get($config, static::FIELD_COMPOSITE_KEYS, ['id']);
        $this->localIndexes = (array)Arr::get($config, static::FIELD_INDEXES);

        return $this;
    }

    public function hasCompositeKeys(): bool
    {
        return !empty($this->compositeKeys);
    }

    public function hasAdditional(): bool
    {
        return !is_null($this->additional);
    }

    public function getStrategy(): PartitionDbStrategyEnum
    {
        return $this->strategy;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getTableSuffixPattern(): string
    {
        return $this->tableSuffixPattern;
    }

    public function getAdditional(): ?BaseDbPartitionSettings
    {
        return $this->additional;
    }
}
