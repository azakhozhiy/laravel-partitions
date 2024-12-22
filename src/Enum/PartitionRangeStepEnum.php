<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Enum;

use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\DTO\RangeInfoDTO;

enum PartitionRangeStepEnum: string
{
    case DAY = 'DAY';
    case WEEK = 'WEEK';
    case MONTH = 'MONTH';
    case QUARTER = 'QUARTER';
    case YEAR = 'YEAR';

    case THOUSAND = 'THOUSAND';
    case ONE_HUNDRED_THOUSANDS = 'ONE_HUNDRED_THOUSANDS';
    case MILLION = 'MILLION';
    case TWO_MILLIONS = 'TWO_MILLIONS';
    case FIVE_MILLIONS = 'FIVE_MILLIONS';

    public function isNumericStep(): bool
    {
        return $this === self::THOUSAND
            || $this === self::ONE_HUNDRED_THOUSANDS
            || $this === self::MILLION
            || $this === self::TWO_MILLIONS
            || $this === self::FIVE_MILLIONS;
    }

    public static function getNumericSteps(): array
    {
        return [
            self::THOUSAND,
            self::ONE_HUNDRED_THOUSANDS,
            self::MILLION,
            self::TWO_MILLIONS,
            self::FIVE_MILLIONS,
        ];
    }

    public static function getTimestampSteps(): array
    {
        return [
            self::DAY,
            self::WEEK,
            self::MONTH,
            self::QUARTER,
            self::YEAR,
        ];
    }

    public function isTimestampStep(): bool
    {
        return $this === self::DAY
            || $this === self::WEEK
            || $this === self::MONTH
            || $this === self::YEAR
            || $this === self::QUARTER;
    }

    public function getNumericStepSize(): int
    {
        return match ($this) {
            self::THOUSAND => 1000,
            self::ONE_HUNDRED_THOUSANDS => 100000,
            self::MILLION => 1000000,
            self::TWO_MILLIONS => 2000000,
            self::FIVE_MILLIONS => 5000000,
            default => throw new RuntimeException('Unexpected match value'),
        };
    }

    public function calculateRangeInfo(Carbon|int|null $rangeBaseValue): RangeInfoDTO
    {
        $baseValueIsNull = $rangeBaseValue === null;
        if ($rangeBaseValue === null) {
            if ($this->isNumericStep()) {
                $rangeBaseValue = 0;
            }

            if ($this->isTimestampStep()) {
                $rangeBaseValue = now();
            }
        }

        if (!($rangeBaseValue instanceof Carbon) && $this->isTimestampStep()) {
            throw new RuntimeException(
                "Base value for calculation timestamp range info should be Carbon instance."
            );
        }

        if (!is_int($rangeBaseValue) && $this->isNumericStep()) {
            throw new RuntimeException(
                "Base value for calculation numeric range info should be integer."
            );
        }

        if ($this->isNumericStep()) {
            if ($rangeBaseValue === 0) {
                $rangeStart = $rangeBaseValue;
                $rangeEnd = $rangeStart + $this->getNumericStepSize();
            } else {
                $rangeStart = $rangeBaseValue + 1;
                $rangeEnd = $rangeStart + $this->getNumericStepSize() - 1;
            }

            $tablePrefix = $rangeStart.'_'.$rangeEnd;

            return new RangeInfoDTO($this, $rangeStart, $rangeEnd, $tablePrefix);
        }

        switch ($this) {
            case self::MONTH:
                $rangeBaseValue = $baseValueIsNull ? $rangeBaseValue : $rangeBaseValue->addDay();
                $rangeStart = $rangeBaseValue->copy()->startOfMonth();
                $rangeEnd = $rangeBaseValue->copy()->endOfMonth();
                $tablePrefix = Str::lower($rangeBaseValue->format('M_Y'));

                break;
            case self::YEAR:
                $rangeBaseValue = $baseValueIsNull ? $rangeBaseValue : $rangeBaseValue->addDay();
                $rangeStart = $rangeBaseValue->copy()->startOfYear();
                $rangeEnd = $rangeBaseValue->copy()->endOfYear();
                $tablePrefix = Str::lower($rangeBaseValue->format('Y'));

                break;
            case self::WEEK:
                $rangeBaseValue = $baseValueIsNull ? $rangeBaseValue : $rangeBaseValue->addDay();
                $rangeStart = $rangeBaseValue->copy()->startOfWeek();
                $rangeEnd = $rangeBaseValue->copy()->endOfWeek();
                $tablePrefix = Str::lower($rangeBaseValue->format('W_Y'));

                break;
            case self::DAY:
                $rangeBaseValue = $baseValueIsNull ? $rangeBaseValue : $rangeBaseValue->addDay();
                $rangeStart = $rangeBaseValue->copy()->startOfDay();
                $rangeEnd = $rangeBaseValue->copy()->endOfDay();
                $tablePrefix = Str::lower($rangeBaseValue->format('d_M_Y'));
                break;
            case self::QUARTER:
                $rangeBaseValue = $baseValueIsNull ? $rangeBaseValue : $rangeBaseValue->addDay();
                $rangeStart = $rangeBaseValue->copy()->startOfQuarter();
                $rangeEnd = $rangeBaseValue->copy()->endOfQuarter();
                $tablePrefix = Str::lower(
                    "q".$rangeEnd->quarter."_".$rangeEnd->format('Y')
                );

                break;
            default:
                throw new RuntimeException('Unexpected value');
        }

        return new RangeInfoDTO($this, $rangeStart, $rangeEnd, $tablePrefix);
    }
}
