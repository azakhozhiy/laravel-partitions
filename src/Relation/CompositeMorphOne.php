<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Relation;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

class CompositeMorphOne extends CompositeMorphOneOrMany
{
    use SupportsDefaultModels;

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array<int,Model>  $models
     * @param  string  $relation
     * @return array<int,Model>
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchOne($models, $results, $relation);
    }

    public function getResults()
    {
        foreach ($this->getParentKeys() as $parentKey) {
            if (is_null($parentKey)) {
                return $this->getDefaultFor($this->parent);
            }
        }

        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param  Model  $parent
     * @return Model
     */
    public function newRelatedInstanceFor(Model $parent): Model
    {
        $instance = $this->related->newInstance();

        foreach ($this->getForeignKeyNames() as $index => $foreignKeyName) {
            $instance->setAttribute($foreignKeyName, $parent->{$this->localKeys[$index]});
        }

        if (!$this->morphTypeIsPartOfForeignKeys()) {
            $instance->setAttribute($this->getMorphType(), $this->morphClass);
        }

        return $instance;
    }
}
