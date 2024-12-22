<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\DTO;

use Carbon\Carbon;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeStepEnum;

class RangeInfoDTO
{
    public function __construct(
        protected PartitionRangeStepEnum $stepEnum,
        protected Carbon|int $rangeStart,
        protected Carbon|int $rangeEnd,
        protected string $rangeSuffix
    ) {
    }

    public function getStepEnum(): PartitionRangeStepEnum
    {
        return $this->stepEnum;
    }

    public function getRangeStart(): int|Carbon
    {
        return $this->rangeStart;
    }

    public function getRangeEnd(): int|Carbon
    {
        return $this->rangeEnd;
    }

    public function getRangeSuffix(): string
    {
        return $this->rangeSuffix;
    }

    public function getFormattedRangeStart(): string
    {
        if (is_int($this->rangeStart)) {
            return (string)$this->rangeStart;
        }

        return $this->rangeStart->format('Y-m-d H:i:s');
    }

    public function getFormattedRangeEnd(): string
    {
        if (is_int($this->rangeEnd)) {
            return (string)$this->rangeEnd;
        }

        return $this->rangeEnd->format('Y-m-d H:i:s');
    }
}
