<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

abstract class CompositeHasOneOrMany extends Relation
{
    /**
     * The foreign key of the parent model.
     *
     * @var array<int,string>
     */
    protected array $foreignKeys;

    /**
     * The local key of the parent model.
     *
     * @var array<int,string>
     */
    protected array $localKeys;

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
     * Create a new has one or many relationship instance.
     *
     * @param  Builder  $query
     * @param  Model  $parent
     * @param  array<int,string>  $foreignKeys
     * @param  array<int,string>  $localKeys
     * @param  string  $glue
     */
    public function __construct(Builder $query, Model $parent, array $foreignKeys, array $localKeys, string $glue)
    {
        $this->localKeys = $localKeys;
        $this->foreignKeys = $foreignKeys;
        $glue = strtolower($glue);

        if (!in_array($glue, ['and', 'or'], true)) {
            throw new InvalidArgumentException('The glue must be either "and" or "or".');
        }

        $this->compositeGlue = $glue;

        parent::__construct($query, $parent);
    }

    /**
     * Create and return an un-saved instance of the related model.
     *
     * @param  array<string,mixed>  $attributes
     * @return Model
     */
    public function make(array $attributes = []): Model
    {
        $instance = $this->related->newInstance($attributes);

        $this->setForeignAttributesForCreate($instance);

        return $instance;
    }

    /**
     * Set the base constraints on the relation query.
     */
    public function addConstraints(): void
    {
        if (!static::$constraints) {
            return;
        }

        $this->query->where(function ($query): void {
            foreach ($this->getParentKeys() as $index => $parentKey) {
                $this->query->where($this->foreignKeys[$index], '=', $parentKey);

                $this->query->whereNotNull($this->foreignKeys[$index]);
            }
        });
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array<int,Model>  $models
     */
    public function addEagerConstraints(array $models): void
    {
        // Wrap everything in a "where" clause
        $this->query->where(function ($query) use ($models): void {
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
                    $this->foreignKeys,
                    array_map(static fn ($localKey) => $model->{$localKey}, $this->localKeys)
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
     * Match the eagerly loaded results to their single parents.
     *
     * @param  array<int,Model>  $models
     * @param  Collection<int,Model>  $results
     * @param  string  $relation
     * @return array<int,Model>
     */
    public function matchOne(array $models, Collection $results, string $relation): array
    {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array<int,Model>  $models
     * @param  Collection<int,Model>  $results
     * @param  string  $relation
     * @return array<int,Model>
     */
    public function matchMany(array $models, Collection $results, string $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'many');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array<int,Model>  $models
     * @param  Collection  $results
     * @param  string  $relation
     * @param  'one'|'many'  $type
     *
     * @return array<int,Model>
     */
    protected function matchOneOrMany(array $models, Collection $results, string $relation, string $type): array
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            $dictionaryKey = json_encode(
                array_map(
                    static fn (string $localKey): mixed => $model->getAttribute($localKey),
                    $this->localKeys
                )
            );

            if (isset($dictionary[$dictionaryKey])) {
                $model->setRelation(
                    $relation,
                    $this->getRelationValue($dictionary, $dictionaryKey, $type)
                );
            }
        }

        return $models;
    }

    /**
     * Get the value of a relationship by one or many type.
     *
     * @param  array<string,array<int,Model>>  $dictionary
     * @param  string  $key
     * @param  string  $type
     * @return Collection<int,Model>|Model|null
     */
    protected function getRelationValue(array $dictionary, string $key, string $type)
    {
        $value = $dictionary[$key];

        return $type === 'one'
            ? (reset($value) ?: null)
            : $this->related->newCollection($value);
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  Collection<int,Model>  $results
     * @return array<string,array<int,Model>>
     */
    protected function buildDictionary(Collection $results)
    {
        $foreignKeys = $this->getForeignKeyNames();

        return $results->mapToDictionary(function ($result) use ($foreignKeys) {
            $key = json_encode(
                array_map(static fn ($foreign) => $result->{$foreign}, $foreignKeys)
            );

            return [$key => $result];
        })->all();
    }

    /**
     * Find a model by its primary key or return new instance of the related model.
     *
     * @param  mixed  $id
     * @param  array<int,string>  $columns
     * @return Model
     */
    public function findOrNew($id, $columns = ['*']): Model
    {
        if (is_null($instance = $this->getQuery()->find($id, $columns))) {
            $instance = $this->related->newInstance();

            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
     *
     * @param  array<string,mixed>  $attributes
     * @param  array<string,mixed>  $values
     * @return Model
     */
    public function firstOrNew(array $attributes = [], array $values = []): Model
    {
        if (is_null($instance = $this->getQuery()->where($attributes)->first())) {
            $instance = $this->related->newInstance($attributes + $values);

            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related record matching the attributes or create it.
     *
     * @param  array<string,mixed>  $attributes
     * @param  array<string,mixed>  $values
     * @return Model
     */
    public function firstOrCreate(array $attributes = [], array $values = []): Model
    {
        if (is_null($instance = $this->getQuery()->where($attributes)->first())) {
            $instance = $this->create($attributes + $values);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
     *
     * @param  array<string,mixed>  $attributes
     * @param  array<string,mixed>  $values
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $instance = $this->firstOrNew($attributes);

        $instance->fill($values)->save();

        return $instance;
    }

    /**
     * Attach a model instance to the parent model.
     *
     * @param  Model  $model
     * @return Model|false
     */
    public function save(Model $model): Model|bool
    {
        $this->setForeignAttributesForCreate($model);

        return $model->save() ? $model : false;
    }

    /**
     * Attach a collection of models to the parent instance.
     *
     * @param  iterable<Model>  $models
     * @return iterable<Model>
     */
    public function saveMany(array $models = [])
    {
        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }

    /**
     * Create a new instance of the related model.
     *
     * @param  array<string,mixed>  $attributes
     * @return Model
     */
    public function create(array $attributes = []): Model
    {
        $instance = $this->related->newInstance($attributes);

        $this->setForeignAttributesForCreate($instance);

        $instance->save();

        return $instance;
    }

    /**
     * Create a Collection of new instances of the related model.
     *
     * @param  array<int,array<string,mixed>>  $records
     * @return Collection<int,Model>
     */
    public function createMany(array $records): Collection
    {
        $instances = $this->related->newCollection();

        foreach ($records as $record) {
            $instances->push($this->create($record));
        }

        return $instances;
    }

    /**
     * Set the foreign ID for creating a related model.
     *
     * @param  Model  $model
     */
    protected function setForeignAttributesForCreate(Model $model): void
    {
        $parentKeys = $this->getParentKeys();

        foreach ($this->getForeignKeyNames() as $index => $foreignKey) {
            $model->setAttribute($foreignKey, $parentKeys[$index]);
        }
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  Builder<Model>  $query
     * @param  Builder<Model>  $parentQuery
     * @param  array<int,string>  $columns
     * @return Builder<Model>
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $query->select($columns)->where(function ($query): void {
            $foreignKeys = $this->getQualifiedForeignKeyNames();

            foreach ($this->getQualifiedParentKeyNames() as $index => $parentKey) {
                $query->whereColumn($parentKey, '=', $foreignKeys[$index]);
            }
        });

        return $query;
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param  Builder  $query
     * @param  Builder<Model>  $parentQuery
     * @param  array<int,string>  $columns
     * @return Builder
     */
    public function getRelationExistenceQueryForSelfRelation(
        Builder $query,
        Builder $parentQuery,
        $columns = ['*']
    ): Builder {
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        $query->select($columns)->where(function ($query) use ($hash): void {
            $foreignKeys = $this->getForeignKeyNames();

            foreach ($this->getQualifiedParentKeyNames() as $index => $parentKey) {
                $query->whereColumn($parentKey, '=', $hash.'.'.$foreignKeys[$index]);
            }
        });

        return $query;
    }

    /**
     * Adds the constraints for a relationship join.
     *
     * @link https://github.com/tylernathanreed/laravel-relation-joins
     *
     * @param  Builder  $query
     * @param  Builder<Model>  $parentQuery
     * @param  string  $type
     * @param  string|null  $alias
     * @return Builder
     */
    public function getRelationJoinQuery(Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if (is_null($alias) && $query->getQuery()->from == $parentQuery->getQuery()->from) {
            $alias = $this->getRelationCountHash();
        }

        if (!is_null($alias) && $alias != $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$alias);

            $query->getModel()->setTable($alias);
        }

        return $query->where(function ($query): void {
            $foreignKeys = $this->getForeignKeyNames();

            foreach ($this->getQualifiedParentKeyNames() as $index => $parentKey) {
                $query->whereColumn($query->qualifyColumn($foreignKeys[$index]), '=', $parentKey);
            }
        });
    }

    /**
     * Get a relationship join table hash.
     *
     * @param  bool  $incrementJoinCount
     */
    public function getRelationCountHash($incrementJoinCount = true): string
    {
        return 'laravel_reserved_'.($incrementJoinCount ? static::$selfJoinCount++ : static::$selfJoinCount);
    }

    /**
     * Get the key value of the parent's local keys.
     *
     * @return array<int,mixed>
     */
    public function getParentKeys(): array
    {
        return array_map(fn (string $localKey): mixed => $this->parent->getAttribute($localKey), $this->localKeys);
    }

    /**
     * Get the fully qualified parent key names.
     *
     * @return array<int,string>
     */
    public function getQualifiedParentKeyNames(): array
    {
        return array_map(fn (string $localKey): string => $this->parent->qualifyColumn($localKey), $this->localKeys);
    }

    /**
     * Get the plain foreign keys.
     *
     * @return array<int,string>
     */
    public function getForeignKeyNames(): array
    {
        return array_map(static function (string $foreignKey): string {
            $segments = explode('.', $foreignKey);

            return end($segments);
        }, $this->getQualifiedForeignKeyNames());
    }

    /**
     * Get the foreign keys for the relationship.
     *
     * @return array<int,string>
     */
    public function getQualifiedForeignKeyNames(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Get the local key for the relationship.
     *
     * @return array<int,string>
     */
    public function getLocalKeyNames(): array
    {
        return $this->localKeys;
    }
}
