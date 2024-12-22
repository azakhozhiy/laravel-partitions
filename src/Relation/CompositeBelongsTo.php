<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

class CompositeBelongsTo extends Relation
{
    /** @use SupportsDefaultModels */
    use SupportsDefaultModels;

    /**
     * The child model instance of the relation.
     *
     * @var Model
     */
    protected Model $child;

    /**
     * The foreign keys of the parent model.
     *
     * @var array<int,string>
     */
    protected array $foreignKeys;

    /**
     * The associated keys on the parent model.
     *
     * @var array<int,string>
     */
    protected array $ownerKeys;

    /**
     * The name of the relationship.
     */
    protected string $relationName;

    /**
     * The glue between each composite keys when making the query.
     */
    protected string $compositeGlue;

    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param  Builder  $query
     * @param  Model  $child
     * @param  array<int,string>  $foreignKeys
     * @param  array<int,string>  $ownerKeys
     * @param  string  $relationName
     * @param  string  $glue
     */
    public function __construct(
        Builder $query,
        Model $child,
        array $foreignKeys,
        array $ownerKeys,
        string $relationName,
        string $glue
    ) {
        $this->ownerKeys = $ownerKeys;
        $this->relationName = $relationName;
        $this->foreignKeys = $foreignKeys;
        $glue = strtolower($glue);

        if (!in_array($glue, ['and', 'or'], true)) {
            throw new InvalidArgumentException('The glue must be either "and" or "or".');
        }

        $this->compositeGlue = $glue;

        // In the underlying base relationship class, this variable is referred to as
        // the "parent" since most relationships are not inversed. But, since this
        // one is we will create a "child" variable for much better readability.
        $this->child = $child;

        parent::__construct($query, $child);
    }

    /**
     * Get the results of the relationship.
     *
     * @return ?Model
     */
    public function getResults(): ?Model
    {
        foreach ($this->foreignKeys as $foreignKey) {
            if (is_null($this->child->{$foreignKey})) {
                return $this->getDefaultFor($this->parent);
            }
        }

        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Set the base constraints on the relation query.
     */
    public function addConstraints(): void
    {
        if (!static::$constraints) {
            return;
        }

        // For belongs to relationships, which are essentially the inverse of has one
        // or has many relationships, we need to actually query on the primary key
        // of the related models matching on the foreign key that's on a parent.
        $table = $this->related->getTable();

        $this->query->where(function ($query) use ($table): void {
            foreach ($this->foreignKeys as $index => $foreignKey) {
                $this->query->where(
                    $table.'.'.$this->ownerKeys[$index],
                    '=',
                    $this->child->{$foreignKey}
                );
            }
        });
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array<Model>  $models
     */
    public function addEagerConstraints(array $models): void
    {
        // Wrap everything in a "where" clause
        $this->query->where(function ($query) use ($models): void {
            // Determine the table name
            $table = $this->related->getTable();

            // We can't use a "where in" clause, as there are multiple keys. We instead
            // need to use a nested "or where" clause for each individual model. It's
            // not very speed efficient, but that is the cost of using composites.

            // Initialize a hash map
            $mapped = [];

            // Iterate through each model
            foreach ($models as $model) {
                // We'll grab the primary key names of the related models since it could be set to
                // a non-standard name and not "id". We will then construct the constraint for
                // our eagerly loading query so it returns the proper models from execution.

                // Determine the pair mapping
                $mapping = array_combine(
                    array_map(static fn ($ownerKey) => $table.'.'.$ownerKey, $this->ownerKeys),
                    array_map(static fn ($foreignKey) => $model->{$foreignKey}, $this->foreignKeys)
                );

                // If the pairing has already been mapped, skip it
                if (isset($mapped[$mappedKey = json_encode($mapping)])) {
                    continue;
                }

                // Add an "or where" clause for each key pairing
                if ($this->compositeGlue === 'and') {
                    $query->orWhere(function ($query) use ($mapping): void {
                        foreach ($mapping as $foreignKey => $localKey) {
                            $query->where($foreignKey, '=', $localKey);
                        }
                    });
                } else {
                    $query->orWhere($mapping);
                }

                // To prevent the same entry from appearing multiple times within the sql, we are
                // going to keep track of the combinations that we've already added, and ensure
                // that we only include them once. We have to get cute for the multiple keys.

                // Mark the pairing as mapped
                $mapped[$mappedKey] = true;
            }
        });
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array<Model>  $models
     * @param  string  $relation
     * @return array<int,Model>
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array<int,Model>  $models
     * @param  Collection<int,Model>  $results
     * @param  string  $relation
     * @return array<int,Model>
     * @throws \JsonException
     */
    public function match(array $models, Collection $results, $relation)
    {
        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        foreach ($results as $result) {
            $dictionaryKey = json_encode(array_map(static fn ($ownerKey) => $result->getAttribute($ownerKey), $this->ownerKeys));

            $dictionary[$dictionaryKey] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        foreach ($models as $model) {
            $dictionaryKey = json_encode(array_map(static fn ($foreignKey) => $model->getAttribute($foreignKey), $this->foreignKeys));

            if (isset($dictionary[$dictionaryKey])) {
                $model->setRelation($relation, $dictionary[$dictionaryKey]);
            }
        }

        return $models;
    }

    /**
     * Update the parent model on the relationship.
     *
     * @param  array<string,mixed>  $values
     */
    public function update(array $values): bool
    {
        return $this->getResults()?->fill($values)?->save() ?: false;
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param  array<string,mixed>|Model  $model
     * @return Model
     */
    public function associate(array|Model $model): Model
    {
        $attributes = $model instanceof Model ? array_map(static fn ($ownerKey) => $model->getAttribute($ownerKey), $this->ownerKeys) : array_values($model);

        foreach ($this->foreignKeys as $index => $foreignKey) {
            $this->child->setAttribute($foreignKey, $attributes[$index]);
        }

        if ($model instanceof Model) {
            $this->child->setRelation($this->relationName, $model);
        }

        return $this->child;
    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * @return Model
     */
    public function dissociate(): Model
    {
        foreach ($this->foreignKeys as $foreignKey) {
            $this->child->setAttribute($foreignKey, null);
        }

        return $this->child->setRelation($this->relationName, null);
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  Builder  $query
     * @param  Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        if ($parentQuery->getQuery()->from === $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $query
            ->select($columns)
            ->whereColumn(
                array_combine(
                    $this->getQualifiedForeignKeyNames(),
                    array_map(static fn ($ownerKey) => $query->qualifyColumn($ownerKey), $this->ownerKeys)
                )
            );

        return $query;
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param  Builder  $query
     * @param  Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return Builder
     */
    public function getRelationExistenceQueryForSelfRelation(
        Builder $query,
        Builder $parentQuery,
        array $columns = ['*']
    ): Builder {
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        $query->whereColumn(
            array_combine(
                array_map(static fn ($ownerKey) => $hash.'.'.$ownerKey, $this->ownerKeys),
                $this->getQualifiedForeignKeyNames()
            )
        );

        return $query;
    }

    /**
     * Adds the constraints for a relationship join.
     *
     * @link https://github.com/tylernathanreed/laravel-relation-joins
     *
     * @param  Builder  $query
     * @param  Builder  $parentQuery
     * @param  string  $type
     * @param  string|null  $alias
     * @return Builder
     */
    public function getRelationJoinQuery(
        Builder $query,
        Builder $parentQuery,
        string $type = 'inner',
        string $alias = null
    ) {
        if (is_null($alias) && $query->getQuery()->from === $parentQuery->getQuery()->from) {
            $alias = $this->getRelationCountHash();
        }

        if (!is_null($alias) && $alias !== $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$alias);

            $query->getModel()->setTable($alias);
        }

        $query->whereColumn(
            array_combine(
                $this->getQualifiedForeignKeyNames(),
                array_map(static fn ($ownerKey) => $query->qualifyColumn($ownerKey), $this->ownerKeys)
            )
        );

        return $query;
    }

    /**
     * Get a relationship join table hash.
     *
     * @param  bool  $incrementJoinCount
     * @return string
     */
    public function getRelationCountHash($incrementJoinCount = true)
    {
        return 'laravel_reserved_'.($incrementJoinCount ? static::$selfJoinCount++ : static::$selfJoinCount);
    }

    /**
     * Determine if the related model has an auto-incrementing ID.
     *
     * @return bool
     */
    protected function relationHasIncrementingId()
    {
        return $this->related->getIncrementing() && $this->related->getKeyType() === 'int';
    }

    /**
     * Make a new related instance for the given model.
     *
     * @return Model
     */
    protected function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }

    /**
     * Get the child of the relationship.
     */
    public function getChild(): Model
    {
        return $this->child;
    }

    /**
     * Get the foreign keys of the relationship.
     *
     * @return array<int,string>
     */
    public function getForeignKeyNames(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Get the fully qualified foreign keys of the relationship.
     *
     * @return array<int,string>
     */
    public function getQualifiedForeignKeyNames(): array
    {
        return array_map(fn ($foreignKey) => $this->child->qualifyColumn($foreignKey), $this->foreignKeys);
    }

    /**
     * Get the associated keys of the relationship.
     *
     * @return array<int,string>
     */
    public function getOwnerKeyNames(): array
    {
        return $this->ownerKeys;
    }

    /**
     * Get the fully qualified associated keys of the relationship.
     *
     * @return array<int,string>
     */
    public function getQualifiedOwnerKeyNames(): array
    {
        return array_map(fn ($ownerKey) => $this->related->qualifyColumn($ownerKey), $this->ownerKeys);
    }

    /**
     * Get the name of the relationship.
     */
    public function getRelationName(): string
    {
        return $this->relationName;
    }
}
