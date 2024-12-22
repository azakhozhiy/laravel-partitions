<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings\Database;

use Illuminate\Support\Arr;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeStepEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeTypeEnum;

class RangeDbPartitionSettings extends BaseDbPartitionSettings
{
    public const string FIELD_RANGE_TYPE = 'range_type';
    public const string FIELD_RANGE_STEP = 'range_step';
    public const string FIELD_RANGE_START = 'range_start';
    public const string FIELD_RANGE_END = 'range_end';
    public const string FIELD_AUTO_CREATE_TABLE = 'auto_create_table';
    public const string FIELD_AUTO_CREATE_TABLE_COUNT = 'auto_create_table_count';

    protected PartitionRangeTypeEnum $rangeType;
    protected PartitionRangeStepEnum $rangeStep;
    protected ?string $rangeStart = null;
    protected ?string $rangeEnd = null;
    protected bool $autoCreateTable = true;
    protected int $autoCreateTableCount = 1;

    public static function createByConfig(array $config): static
    {
        $obj = (new static())
            ->fillBaseSettings($config);

        $rangeType = (string)Arr::get($config, self::FIELD_RANGE_TYPE);
        $rangeTypeEnum = PartitionRangeTypeEnum::tryFrom($rangeType);

        if (is_null($rangeTypeEnum)) {
            throw new RuntimeException("Range type '{$rangeType}' not found");
        }

        $rangeStep = (string)Arr::get($config, self::FIELD_RANGE_STEP);
        $rangeStepEnum = $rangeTypeEnum->findRangeStep($rangeStep);

        $obj->rangeType = $rangeTypeEnum;
        $obj->rangeStep = $rangeStepEnum;

        $rangeStart = Arr::get($config, self::FIELD_RANGE_START);
        $rangeEnd = Arr::get($config, self::FIELD_RANGE_END);
        $autoCreateTable = (bool)Arr::get($config, self::FIELD_AUTO_CREATE_TABLE);
        $autoCreateTableCount = (int)Arr::get($config, self::FIELD_AUTO_CREATE_TABLE_COUNT);

        $obj->rangeStart = $rangeStart;
        $obj->rangeEnd = $rangeEnd;
        $obj->autoCreateTable = $autoCreateTable;
        $obj->autoCreateTableCount = $autoCreateTableCount;

        return $obj;
    }

    public function getRangeType(): PartitionRangeTypeEnum
    {
        return $this->rangeType;
    }

    public function getRangeStep(): PartitionRangeStepEnum
    {
        return $this->rangeStep;
    }

    public function getRangeStart(): ?string
    {
        return $this->rangeStart;
    }

    public function getRangeEnd(): ?string
    {
        return $this->rangeEnd;
    }

    public function isAutoCreateTable(): bool
    {
        return $this->autoCreateTable;
    }

    public function getAutoCreateTableCount(): int
    {
        return $this->autoCreateTableCount;
    }
}
