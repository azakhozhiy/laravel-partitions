<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Enum;

use RuntimeException;

enum PartitionRangeTypeEnum: string
{
    case TIMESTAMP = 'TIMESTAMP';
    case NUMERIC = 'NUMERIC';

    /**
     * @return PartitionRangeStepEnum[]
     */
    public function getRangeSteps(): array
    {
        return match ($this) {
            self::TIMESTAMP => PartitionRangeStepEnum::getTimestampSteps(),
            default => PartitionRangeStepEnum::getNumericSteps()
        };
    }

    public function getRangeValueType(): PartitionRangeValueTypeEnum
    {
        return match ($this) {
            self::TIMESTAMP => PartitionRangeValueTypeEnum::STRING,
            self::NUMERIC => PartitionRangeValueTypeEnum::INTEGER
        };
    }

    public function isSupportedRangeStep(PartitionRangeStepEnum $step): bool
    {
        return in_array($step, $this->getRangeSteps(), true);
    }

    public function findRangeStep(string $value): PartitionRangeStepEnum
    {
        foreach ($this->getRangeSteps() as $rangeStep) {
            if ($rangeStep->value === $value) {
                return $rangeStep;
            }
        }

        throw new RuntimeException("Range step $value unsupported.");
    }


}
