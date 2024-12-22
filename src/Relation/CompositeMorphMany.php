<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Relation;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CompositeMorphMany extends CompositeMorphOneOrMany
{
    public function one()
    {
        return CompositeMorphOne::noConstraints(fn () => new CompositeMorphOne(
            $this->getQuery(),
            $this->getParent(),
            $this->morphType,
            $this->foreignKeys,
            $this->localKeys,
            $this->compositeGlue
        ));
    }

    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchMany($models, $results, $relation);
    }

    public function getResults()
    {
        return !empty(array_filter($this->getParentKeys()))
            ? $this->query->get()
            : $this->related->newCollection();
    }

    public function forceCreate(array $attributes = [])
    {
        $attributes[$this->getMorphType()] = $this->morphClass;

        return parent::forceCreate($attributes);
    }

    /**
     * Create a new instance of the related model with mass assignment without raising model events.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function forceCreateQuietly(array $attributes = [])
    {
        return Model::withoutEvents(fn () => $this->forceCreate($attributes));
    }
}
