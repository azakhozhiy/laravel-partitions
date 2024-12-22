<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Composite;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use AZakhozhiy\Laravel\Partitions\Contract\UseCompositePrimaryKeyInterface;
use AZakhozhiy\Laravel\Partitions\DTO\MorphKeys;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeBelongsTo;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeHasMany;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeHasOne;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeMorphMany;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeMorphOne;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeMorphTo;

trait HasCompositeRelations
{
    // BelongsTo
    protected function compositeBelongsTo(
        UseCompositePrimaryKeyInterface $instance,
        string $relation,
        array|string|null $foreignKey,
        array|string|null $ownerKey,
        string $glue = 'or'
    ): CompositeBelongsTo {
        if (is_string($foreignKey)) {
            $foreignKey = [$foreignKey];
        }

        $foreignKey = array_map(
            static fn($keyName) => Str::snake($relation).'_'.$keyName,
            $foreignKey ?: $instance->getKeyNames()
        );

        $ownerKey = $ownerKey ?: $instance->getKeyNames();

        if ($glue) {
            $instance->setCompositeKeyGlueType($glue);
        }

        return $this->newCompositeBelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey,
            $relation,
            $instance->getCompositeKeyGlueType()
        );
    }

    protected function newCompositeBelongsTo(
        Builder $query,
        Model $child,
        array $foreignKeys,
        array $ownerKeys,
        string $relation,
        string $glue
    ): CompositeBelongsTo {
        return new CompositeBelongsTo(
            $query,
            $child,
            $foreignKeys,
            $ownerKeys,
            $relation,
            $glue
        );
    }

    // HasMany
    protected function compositeHasMany(
        UseCompositePrimaryKeyInterface $instance,
        array|string|null $foreignKeys,
        array|string|null $localKeys,
        string $glue
    ): CompositeHasMany {
        $localKey = $localKeys ?: $this->getKeyNames();
        $foreignKeys = $foreignKeys ?: $this->getForeignKeys();
        $foreignKeys = array_map(
            static fn($foreignKey) => $instance->getTable().'.'.$foreignKey,
            $foreignKeys
        );

        return $this->newCompositeHasMany(
            $instance->newQuery(),
            $this,
            $foreignKeys,
            $localKey,
            $glue
        );
    }

    protected function newCompositeHasMany(
        Builder $query,
        Model $parent,
        array $foreignKeys,
        array $localKeys,
        string $glue
    ): CompositeHasMany {
        return new CompositeHasMany(
            $query,
            $parent,
            $foreignKeys,
            $localKeys,
            $glue
        );
    }

    // HasOne
    protected function compositeHasOne(
        UseCompositePrimaryKeyInterface $instance,
        array|string|null $foreignKey = null,
        array|string|null $localKey = null,
        string $glue = 'or'
    ): CompositeHasOne {
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

    protected function newCompositeHasOne(
        Builder $query,
        Model $parent,
        array $foreignKeys,
        array $localKeys,
        string $glue
    ): CompositeHasOne {
        return new CompositeHasOne(
            $query,
            $parent,
            $foreignKeys,
            $localKeys,
            $glue
        );
    }

    // MorphMany
    protected function compositeMorphMany(
        UseCompositePrimaryKeyInterface $instance,
        MorphKeys $morphKeys,
        array|string|null $localKey = null,
        string $glue = 'or'
    ): CompositeMorphMany {
        $instanceTable = $instance->getTable();
        $localKey = $localKey ?: $this->getKeyNames();
        $foreignKey = array_map(
            static fn(string $foreignKey) => $instanceTable.'.'.$foreignKey,
            $morphKeys->hasForeignKeys() ? $morphKeys->getForeignKeys() : $this->getForeignKeys()
        );

        return $this->newCompositeMorphMany(
            $instance->newQuery(),
            $this,
            $instanceTable.'.'.$morphKeys->getType(),
            $foreignKey,
            $localKey,
            $glue
        );
    }

    protected function newCompositeMorphMany(
        Builder $query,
        Model $parent,
        string $type,
        array $foreignKeys,
        array $localKeys,
        string $glue
    ): CompositeMorphMany {
        return new CompositeMorphMany(
            $query,
            $parent,
            $type,
            $foreignKeys,
            $localKeys,
            $glue
        );
    }

    // MorphOne
    protected function compositeMorphOne(
        UseCompositePrimaryKeyInterface $instance,
        MorphKeys $morphKeys,
        array|string|null $localKey,
        string $glue = 'or'
    ): CompositeMorphOne {
        $localKey = $localKey ?: $this->getKeyNames();
        $instanceTable = $instance->getTable();

        // Build foreign keys
        $foreignKey = array_map(
            static fn(string $foreignKey) => $instanceTable.'.'.$foreignKey,
            $morphKeys->hasForeignKeys() ? $morphKeys->getForeignKeys() : $this->getForeignKeys()
        );

        return $this->newCompositeMorphOne(
            $instance->newQuery(),
            $this,
            $instanceTable.'.'.$morphKeys->getType(),
            $foreignKey,
            $localKey,
            $glue
        );
    }

    protected function newCompositeMorphOne(
        Builder $query,
        Model $parent,
        string $type,
        array $foreignKeys,
        array $localKeys,
        string $glue
    ): CompositeMorphOne {
        return new CompositeMorphOne(
            $query,
            $parent,
            $type,
            $foreignKeys,
            $localKeys,
            $glue
        );
    }

    // MorphTo
    protected function compositeMorphTo(): CompositeMorphTo
    {
    }


    protected function newCompositeMorphTo(
        Builder $query,
        Model $parent,
        array $foreignKeys,
        array $ownerKeys,
        string $type,
        string $relation,
        string $glue
    ): CompositeMorphTo {
        return new CompositeMorphTo(
            $query,
            $parent,
            $foreignKeys,
            $ownerKeys,
            $relation,
            $type,
            $glue
        );
    }

    protected function getMorphsKeys($relationName, $type, $id): MorphKeys
    {
        $morphKeys = new MorphKeys($type ?: $relationName.'_type', $relationName);

        if (is_array($id)) {
            foreach ($id as $partId) {
                $morphKeys->addForeignKey($partId);
            }
        }

        if (is_string($id)) {
            $morphKeys->addForeignKey($id);
        }

        if (is_null($id)) {
            $morphKeys->addForeignKey($relationName.'_id');
        }

        return $morphKeys;
    }
}
