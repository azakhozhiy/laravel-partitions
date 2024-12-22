<?php

namespace AZakhozhiy\Laravel\Partitions\Tests\TestData\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use AZakhozhiy\Laravel\Partitions\Concern\Partition\ModelPartitioned;
use AZakhozhiy\Laravel\Partitions\Contract\ModelPartitionedInterface;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionDbStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeStepEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeTypeEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Service\ModelPartitionedSettings;
use AZakhozhiy\Laravel\Partitions\Settings\BasePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\HashDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\RangeDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\DedicatedPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;

/**
 * @property string $uuid
 * @property string $merchant_id
 * @property int $partition_number
 */
class Transaction extends Model implements ModelPartitionedInterface
{
    use ModelPartitioned;

    public const string UUID = 'uuid';
    public const string MERCHANT_ID = 'merchant_id';
    public const string PARTITION_NUMBER = 'partition_number';

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(
            Merchant::class,
            self::MERCHANT_ID,
        );
    }

    public static function getPartitionedSettings(): ModelPartitionedSettings
    {
        return new ModelPartitionedSettings([
            PartitionStrategyEnum::BASE_TABLE->arrayKey() => [
                BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => 'transactions',

                BasePartitionSettings::FIELD_DB_PARTITION => [
                    HashDbPartitionSettings::FIELD_COUNT => 3,
                    BaseDbPartitionSettings::FIELD_STRATEGY => PartitionDbStrategyEnum::HASH->value,
                    BaseDbPartitionSettings::FIELD_TABLE_SUFFIX_PATTERN => 'part_%s',
                    BaseDbPartitionSettings::FIELD_COLUMN => static::MERCHANT_ID,
                    BaseDbPartitionSettings::FIELD_COMPOSITE_KEYS => [
                        static::UUID,
                        static::MERCHANT_ID,
                    ],
                ],
            ],
            PartitionStrategyEnum::HASH_VIA_CODE->arrayKey() => [
                BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => 'transactions_hash_via_code_%s',
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_PARTITION_COUNT => 5,
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_FIRST_PARTITION_NUMBER => 1,
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_DEFAULT_PARTITION_NUMBER => 1,
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_ALGO => 'murmur',

                BasePartitionSettings::FIELD_DB_PARTITION => [
                    HashDbPartitionSettings::FIELD_COUNT => 3,
                    BaseDbPartitionSettings::FIELD_STRATEGY => PartitionDbStrategyEnum::HASH->value,
                    BaseDbPartitionSettings::FIELD_TABLE_SUFFIX_PATTERN => 'part_%s',
                    BaseDbPartitionSettings::FIELD_COLUMN => static::MERCHANT_ID,
                    BaseDbPartitionSettings::FIELD_COMPOSITE_KEYS => [
                        static::UUID,
                        static::MERCHANT_ID,
                    ],
                ],
            ],
            PartitionStrategyEnum::DEDICATED->arrayKey() => [
                // Dedicated table
                BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => 'transactions_dedicated_merchant_%s',

                BasePartitionSettings::FIELD_DB_PARTITION => [
                    BaseDbPartitionSettings::FIELD_STRATEGY => PartitionDbStrategyEnum::RANGE->value,
                    BaseDbPartitionSettings::FIELD_TABLE_SUFFIX_PATTERN => '%s',
                    BaseDbPartitionSettings::FIELD_COLUMN => Model::CREATED_AT,

                    RangeDbPartitionSettings::FIELD_RANGE_TYPE => PartitionRangeTypeEnum::TIMESTAMP->value,
                    RangeDbPartitionSettings::FIELD_RANGE_STEP => PartitionRangeStepEnum::YEAR->value,
                    RangeDbPartitionSettings::FIELD_AUTO_CREATE_TABLE => true,
                    RangeDbPartitionSettings::FIELD_AUTO_CREATE_TABLE_COUNT => 4,

                    BaseDbPartitionSettings::FIELD_COMPOSITE_KEYS => [
                        static::UUID,
                        static::CREATED_AT,
                    ]
                ],
            ],
        ], static::getPartitionSourceStrategies());
    }

    public static function getPartitionSourceStrategies(): array
    {
        return Merchant::getSupportedPartitionStrategies();
    }

    public static function hasPartitionNumberColumn(): bool
    {
        return true;
    }
}