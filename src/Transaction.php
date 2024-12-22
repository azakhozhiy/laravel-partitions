<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions;

use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Concern\Partition\HasPartitionRelations;
use AZakhozhiy\Laravel\Partitions\Concern\Partition\ModelPartitioned;

class Transaction extends Model
{
    use ModelPartitioned;
    use HasPartitionRelations;
}
