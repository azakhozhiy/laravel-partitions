<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Partition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Concern\Composite\HasCompositeRelations;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\DTO\MorphKeys;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Helper\ModelHelper;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeBelongsTo;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeHasMany;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeHasOne;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeMorphMany;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeMorphOne;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeMorphTo;

/**
 * @mixin Model
 */
trait HasPartitionRelations
{
    use HasCompositeRelations;

    protected function newPartitionRelatedInstance(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        string $class
    ): Model|ModelPartitionedInterface {
        $instance = $this->newRelatedInstance($class);

        if ($partitionKey && $partitionStrategy && $instance instanceof ModelPartitionedInterface) {
            $instance = $instance->setTableByPartitionKeys($partitionStrategy, $partitionKey);
        }

        return $instance;
    }

    public function partitionBelongsTo(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        string $related,
        array|string|null $foreignKey = null,
        array|string|null $ownerKey = null,
        ?string $relation = null,
        string $glue = 'or'
    ): CompositeBelongsTo|BelongsTo {
        // Take relation name from caller
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        // Init instance
        $instance = $this->newPartitionRelatedInstance($partitionStrategy, $partitionKey, $related);

        // Check composite primary key
        if (ModelHelper::isCompositePrimaryKey($instance)) {
            return $this->compositeBelongsTo(
                $instance,
                $relation,
                $foreignKey,
                $ownerKey,
                $glue
            );
        }

        if (is_array($foreignKey)) {
            throw new RuntimeException("Foreign key should be string or null.");
        }

        $foreignKey = $foreignKey ?: Str::snake($relation).'_'.$instance->getKeyName();
        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey,
            $relation
        );
    }

    public function partitionHasMany(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        string $related,
        array|string|null $foreignKey = null,
        array|string|null $localKey = null,
        string $glue = 'or'
    ): CompositeHasMany|HasMany {
        // Init instance
        $instance = $this->newPartitionRelatedInstance($partitionStrategy, $partitionKey, $related);

        // Check composite primary key
        if (ModelHelper::isCompositePrimaryKey($this)) {
            return $this->compositeHasMany($instance, $foreignKey, $localKey, $glue);
        }

        if (is_array($foreignKey)) {
            throw new RuntimeException("Foreign key should be string or null.");
        }

        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasMany(
            $instance->newQuery(),
            $this,
            $instance->getTable().'.'.$foreignKey,
            $localKey
        );
    }

    public function partitionHasOne(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        $related,
        array|string|null $foreignKey = null,
        array|string|null $localKey = null,
        ?string $glue = 'or'
    ): CompositeHasOne|HasOne {
        $instance = $this->newPartitionRelatedInstance($partitionStrategy, $partitionKey, $related);

        if (ModelHelper::isCompositePrimaryKey($this)) {
            $localKey = $localKey ?: $this->getKeyNames();
            $foreignKey = array_map(
                static fn(string $foreignKey) => $instance->getTable().'.'.$foreignKey,
                $foreignKey ?: $this->getForeignKeys()
            );

            return $this->newCompositeHasOne(
                $instance->newQuery(),
                $this,
                $foreignKey,
                $localKey,
                $glue
            );
        }

        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasOne(
            $instance->newQuery(),
            $this,
            $instance->getTable().'.'.$foreignKey,
            $localKey
        );
    }

    public function partitionMorphMany(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        string $related,
        string $name,
        ?string $type = null,
        array|string|null $id = null,
        array|string|null $localKey = null,
        string $glue = 'or'
    ): CompositeMorphMany|MorphMany {
        $morphKeys = $this->getMorphsKeys($name, $type, $id);

        // Instance
        $instance = $this->newPartitionRelatedInstance($partitionStrategy, $partitionKey, $related);
        $instanceTable = $instance->getTable();

        // Composite relation
        if (ModelHelper::isCompositePrimaryKey($this)) {
            return $this->compositeMorphMany(
                $instance,
                $morphKeys,
                $localKey,
                $glue
            );
        }

        // Simple MorphMany
        $foreignKey = $morphKeys->getFirstForeignKey();
        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphMany(
            $instance->newQuery(),
            $this,
            $instanceTable.'.'.$morphKeys->getType(),
            $instanceTable.'.'.$foreignKey,
            $localKey
        );
    }

    public function partitionMorphOne(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $partitionKey,
        string $related,
        string $name,
        string|null $type = null,
        array|string|null $foreignKeys = null,
        array|string|null $localKey = null,
        string $glue = 'or'
    ): CompositeMorphOne|MorphOne {
        $morphKeys = $this->getMorphsKeys($name, $type, $foreignKeys);

        // Instance
        $instance = $this->newPartitionRelatedInstance($partitionStrategy, $partitionKey, $related);
        $instanceTable = $instance->getTable();

        // With composite primary key
        if (ModelHelper::isCompositePrimaryKey($this)) {
            return $this->compositeMorphOne(
                $instance,
                $morphKeys,
                $localKey,
                $glue
            );
        }

        // With simple primary key
        $localKey = $localKey ?: $this->getKeyName();
        $foreignKey = $morphKeys->getFirstForeignKey();

        return $this->newMorphOne(
            $instance->newQuery(),
            $this,
            $instanceTable.'.'.$type,
            $instanceTable.'.'.$foreignKey,
            $localKey
        );
    }

    public function partitionMorphTo(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $morphToPartitionKey,
        string $relationName = null,
        string $type = null,
        array|string|null $id = null,
        array|string|null $ownerKey = null,
        string $glue = 'or'
    ): MorphTo|CompositeMorphTo {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $morphKeys = $this->getMorphsKeys(Str::snake($relationName), $type, $id);

        $isEagerTo = is_null($class = $this->getAttributeFromArray($morphKeys->getType())) || $class === '';

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. In this case we'll just pass in a dummy query where we
        // need to remove any eager loads that may already be defined on a model.
        if ($isEagerTo) {
            return $this->partitionMorphEagerTo($morphKeys, $ownerKey, $glue);
        }

        return $this->partitionMorphInstanceTo(
            $partitionStrategy,
            $morphToPartitionKey,
            $class,
            $morphKeys,
            $ownerKey
        );
    }

    protected function partitionMorphEagerTo(
        MorphKeys $morphKeys,
        array|string|null $ownerKey = null,
        string $glue = 'or'
    ): CompositeMorphTo|MorphTo {
        if ($morphKeys->isCompositePrimaryKey()) {
            return $this->newCompositeMorphTo(
                $this->newQuery()->setEagerLoads([]),
                $this,
                $morphKeys->getForeignKeys(),
                $ownerKey,
                $morphKeys->getType(),
                $morphKeys->getRelationName(),
                $glue
            );
        }

        $foreignKey = $morphKeys->hasForeignKeys()
            ? $morphKeys->getForeignKeys()[0]
            : $this->getForeignKey();

        $ownerKey = $ownerKey ?: $this->getKeyName();

        return $this->newMorphTo(
            $this->newQuery()->setEagerLoads([]),
            $this,
            $foreignKey,
            $ownerKey,
            $morphKeys->getType(),
            $morphKeys->getRelationName()
        );
    }

    protected function partitionMorphInstanceTo(
        PartitionStrategyEnum|null $partitionStrategy,
        int|string|null $morphToPartitionKey,
        string $target,
        MorphKeys $morphKeys,
        array|string|null $ownerKey = null,
        string $glue = 'or'
    ): CompositeMorphTo|MorphTo {
        $instance = $this->newPartitionRelatedInstance(
            $partitionStrategy,
            $morphToPartitionKey,
            static::getActualClassNameForMorph($target)
        );


        if (ModelHelper::isCompositePrimaryKey($instance)) {
            $ownerKey = $ownerKey ?? $instance->getKeyNames();

            return $this->newCompositeMorphTo(
                $instance->newQuery(),
                $this,
                $morphKeys->getForeignKeys(),
                $ownerKey,
                $morphKeys->getType(),
                $morphKeys->getRelationName(),
                $glue
            );
        }

        $foreignKey = $morphKeys->getFirstForeignKey();
        $ownerKey = $ownerKey ?: $this->getKeyName();

        return $this->newMorphTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey,
            $morphKeys->getType(),
            $morphKeys->getRelationName()
        );
    }

    public function hasManyThroughPartition(
        $related,
        $through,
        PartitionStrategyEnum|null $throughPartitionStrategy,
        int|string|null $throughPartitionKey,
        $firstKey = null,
        $secondKey = null,
        $localKey = null,
        $secondLocalKey = null
    ): HasManyThrough {
        $through = $this->newRelatedThroughInstance($through);

        if ($throughPartitionKey && $throughPartitionStrategy && $through instanceof ModelPartitionedInterface) {
            $through = $through->setTableByPartitionKeys(
                $throughPartitionStrategy,
                $throughPartitionKey
            );
        }

        // Validate current entity
        if ($this->isCompositePrimaryKey()) {
            throw new RuntimeException(
                "Composite primary keys are not supported for hasManyThrough relation."
            );
        }
        $firstKey = $firstKey ?: $this->getForeignKey();

        // Validate through entity
        if (ModelHelper::isCompositePrimaryKey($through)) {
            throw new RuntimeException(
                "Composite primary keys are not supported for hasManyThrough relation."
            );
        }
        $secondKey = $secondKey ?: $through->getForeignKey();

        $instance = $this->newRelatedInstance($related);

        return $this->newHasManyThrough(
            $instance->newQuery(),
            $this,
            $through,
            $firstKey,
            $secondKey,
            $localKey ?: $this->getKeyName(),
            $secondLocalKey ?: $through->getKeyName()
        );
    }
}
