<?php

namespace AZakhozhiy\Laravel\Partitions\Tests\TestData\Model;

use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Concern\Partition\HasPartitionRelations;
use AZakhozhiy\Laravel\Partitions\Concern\Partition\ModelPartitionSource;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionSourceInterface;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Relation\CompositeHasMany;

class Merchant extends Model implements ModelPartitionSourceInterface
{
    use ModelPartitionSource;
    use HasPartitionRelations;

    public function transactions(): CompositeHasMany
    {
        return $this->partitionHasMany(
            $this->getPartitionStrategy(),
            $this->getKey(),
            Transaction::class,
            Transaction::MERCHANT_ID
        );
    }

    public function getPartitionKey(): string
    {
        return match ($this->getPartitionStrategy()) {
            PartitionStrategyEnum::BASE_TABLE => '',
            PartitionStrategyEnum::HASH_VIA_CODE => 'id',
            PartitionStrategyEnum::DEDICATED => 'name'
        };
    }
}