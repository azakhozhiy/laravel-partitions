<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Repository;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Concern\Partition\PartitionFilterable;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;

abstract class BasePartitionRepository
{
    protected ?string $partitionKey = null;
    protected PartitionStrategyEnum $partitionStrategy;

    public function __construct()
    {
        $this->validateModelClass();
        $this->partitionStrategy = $this->getModelClass()::getDefaultPartitionStrategy();
    }

    public function setPartitionKey(string|int $partitionKey): static
    {
        $this->partitionKey = (string)$partitionKey;

        return $this;
    }

    public function setPartitionStrategy(PartitionStrategyEnum $partitionStrategy): static
    {
        $this->partitionStrategy = $partitionStrategy;

        return $this;
    }

    /**
     * @return class-string<ModelPartitionedInterface>
     */
    abstract public function getModelClass(): string;

    public function validateModelClass(): void
    {
        $modelClass = $this->getModelClass();

        if (!class_exists($modelClass)) {
            throw new RuntimeException("Model class doesn't exist.");
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            throw new RuntimeException("Model should be implement ".Model::class);
        }

        if (!is_subclass_of($modelClass, ModelPartitionedInterface::class)) {
            throw new RuntimeException("Model should implement ".ModelPartitionedInterface::class);
        }
    }

    public function partitionFilter(
        array $data = [],
        ?string $customFilter = null
    ): Builder {
        /** @var class-string<PartitionFilterable|Filterable> $modelClass */
        $modelClass = $this->getModelClass();

        $partitionStrategy = $this->partitionStrategy;
        $partitionKey = $this->partitionKey;

        if ($partitionKey === null) {
            throw new RuntimeException("Partition key can't be null.");
        }

        if (method_exists($modelClass, 'partitionFilter')) {
            return $modelClass::partitionFilter($partitionStrategy, $partitionKey, $data, $customFilter);
        }

        throw new RuntimeException("Model should use ".PartitionFilterable::class." trait.");
    }

    public function query(): Builder
    {
        /** @var class-string<ModelPartitionedInterface> $modelClass */
        $modelClass = $this->getModelClass();

        if ($this->partitionStrategy->supportsQueryByPartitionNumber()) {
            return $modelClass::queryByPartitionNumber($this->partitionStrategy);
        }

        if ($this->partitionKey === null) {
            throw new RuntimeException("Partition key can't be null.");
        }

        return $modelClass::queryByPartitionKey($this->partitionStrategy, $this->partitionKey);
    }

}
