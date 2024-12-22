<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Contract;

use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;

/**
 * @mixin Model
 */
interface ModelPartitionSourceInterface
{
    public function getPartitionStrategy(): PartitionStrategyEnum;

    public static function getSupportedPartitionStrategies(): array;

    public static function getDefaultPartitionStrategy(): PartitionStrategyEnum;

    public function getPartitionKey(): string;

    public static function supportsStrategy(PartitionStrategyEnum $enum): bool;
}
