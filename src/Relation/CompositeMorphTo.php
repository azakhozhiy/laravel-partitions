<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;

class CompositeMorphTo extends CompositeBelongsTo
{
    use InteractsWithDictionary;

    protected string $morphType;

    protected array $dictionary = [];

    public function __construct(
        Builder $query,
        Model $parent,
        array $foreignKeys,
        array $ownerKeys,
        string $type,
        string $relation,
        string $glue
    ) {
        $this->morphType = $type;

        parent::__construct($query, $parent, $foreignKeys, $ownerKeys, $relation, $glue);
    }

    protected function buildDictionary(Collection $models): void
    {
        foreach ($models as $model) {
            if ($model->{$this->morphType}) {
                $morphTypeKey = $this->getDictionaryKey($model->{$this->morphType});

                foreach ($this->foreignKeys as $foreignKey) {
                    $foreignKeyKey = $this->getDictionaryKey($model->{$foreignKey});

                    $this->dictionary[$morphTypeKey][$foreignKeyKey][] = $model;
                }
            }
        }
    }

    protected function morphTypeIsPartOfForeignKeys(): bool
    {
        // Need to check plain morph name in foreign keys
        return in_array($this->getMorphType(), $this->foreignKeys, true);
    }

    public function getMorphType()
    {
        return last(explode('.', $this->morphType));
    }
}
