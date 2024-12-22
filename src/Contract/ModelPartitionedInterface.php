<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Contract;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Service\ModelPartitionedSettings;

/**
 * @mixin Model
 */
interface ModelPartitionedInterface extends UseCompositePrimaryKeyInterface
{
    public static function getPartitionedSettings(): ModelPartitionedSettings;

    /**
     * @return PartitionStrategyEnum[]
     */
    public static function getPartitionSourceStrategies(): array;

    public function setTableByPartitionKeys(PartitionStrategyEnum $enum, string|int ...$keys): static;

    public static function createByPartitionKeys(PartitionStrategyEnum $enum, string|int ...$keys): static;

    public static function queryByPartitionKey(PartitionStrategyEnum $enum, string|int ...$keys): Builder;

    public static function queryByPartitionNumber(PartitionStrategyEnum $enum, int $number): Builder;

    public static function getDefaultPartitionStrategy(): ?PartitionStrategyEnum;
}
