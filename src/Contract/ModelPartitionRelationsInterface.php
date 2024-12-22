<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Contract;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;

interface ModelPartitionRelationsInterface
{
    public function partitionBelongsTo(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        $related,
        $foreignKey = null,
        $ownerKey = null,
        $relation = null
    ): BelongsTo;

    public function hasManyThroughPartition(
        $related,
        $through,
        PartitionStrategyEnum|null $throughPartitionStrategy,
        int|string|null $throughPartitionKey,
        $firstKey = null,
        $secondKey = null,
        $localKey = null,
        $secondLocalKey = null
    ): HasManyThrough;

    public function partitionHasMany(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        $related,
        $foreignKey = null,
        $localKey = null
    ): HasMany;

    public function partitionHasOne(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        $related,
        $foreignKey = null,
        $localKey = null
    ): HasOne;

    public function partitionMorphMany(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        $related,
        $name,
        $type = null,
        $id = null,
        $localKey = null
    ): MorphMany;

    public function partitionMorphTo(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $morphToPartitionKey,
        $name = null,
        $type = null,
        $id = null,
        $ownerKey = null
    ): MorphTo;
}
