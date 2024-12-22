<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class CompositeMorphOneOrMany extends CompositeHasOneOrMany
{
    protected string $morphType;

    /**
     * The class name of the parent model.
     *
     * @var string
     */
    protected string $morphClass;

    /**
     * Create a new composite morph one or many relationship instance.
     *
     * @param  Builder  $query
     * @param  Model  $parent
     * @param  string  $type  column
     * @param  array  $foreignKeys
     * @param  array  $localKeys
     * @param  string  $glue
     */
    public function __construct(
        Builder $query,
        Model $parent,
        string $type,
        array $foreignKeys,
        array $localKeys,
        string $glue
    ) {
        $this->morphType = $type;
        $this->morphClass = $parent->getMorphClass();

        parent::__construct($query, $parent, $foreignKeys, $localKeys, $glue);
    }

    public function addConstraints(): void
    {
        if (!static::$constraints) {
            return;
        }

        if (!$this->morphTypeIsPartOfForeignKeys()) {
            $this->query->where($this->morphType, $this->morphClass);
        }

        $this->query->where(function ($query): void {
            foreach ($this->getParentKeys() as $index => $parentKey) {
                $this->query->where($this->foreignKeys[$index], '=', $parentKey);
                $this->query->whereNotNull($this->foreignKeys[$index]);
            }
        });
    }

    protected function morphTypeIsPartOfForeignKeys(): bool
    {
        // Need to check plain morph name in foreign keys
        return in_array($this->getMorphType(), $this->foreignKeys, true);
    }

    public function addEagerConstraints(array $models): void
    {
        parent::addEagerConstraints($models);

        if (!$this->morphTypeIsPartOfForeignKeys()) {
            $this->getRelationQuery()->where($this->morphType, $this->morphClass);
        }
    }

    /**
     * Create a new instance of the related model. Allow mass-assignment.
     *
     * @param  array  $attributes
     * @return Model
     */
    public function forceCreate(array $attributes = [])
    {
        parent::forceCreate($attributes);
        $attributes[$this->getMorphType()] = $this->morphClass;

        return $this->related->forceCreate($attributes);
    }

    /**
     * Set the foreign ID and type for creating a related model.
     *
     * @param  Model  $model
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model): void
    {
        parent::setForeignAttributesForCreate($model);

        if (!$this->morphTypeIsPartOfForeignKeys()) {
            $model->{$this->getMorphType()} = $this->morphClass;
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
        $query = parent::getRelationExistenceQuery($query, $parentQuery, $columns);

        if (!$this->morphTypeIsPartOfForeignKeys()) {
            $query = $query->where($query->qualifyColumn($this->getMorphType()), $this->morphClass);
        }

        return $query;
    }


    /**
     * Get the foreign key "type" name.
     *
     * @return string
     */
    public function getQualifiedMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the plain morph type name without the table.
     *
     * @return string
     */
    public function getMorphType()
    {
        return last(explode('.', $this->morphType));
    }

    /**
     * Get the class name of the parent model.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }
}
