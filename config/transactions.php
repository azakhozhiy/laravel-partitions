<?php

use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Settings\BasePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionDbStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Settings\Database\HashDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\Database\RangeDbPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeTypeEnum;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionRangeStepEnum;
use AZakhozhiy\Laravel\Partitions\Transaction;
use AZakhozhiy\Laravel\Partitions\Settings\DedicatedPartitionSettings;
use Illuminate\Database\Eloquent\Model;
use AZakhozhiy\Laravel\Partitions\Enum\DbIndexTypeEnum;

return [
    'db' => [
        'partition' => [
            'settings' => [
                PartitionStrategyEnum::HASH_VIA_CODE->arrayKey() => [
                    BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => 'transactions_hash_via_code_%s',

                    // Hash via code
                    HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_PARTITION_COUNT => 5,
                    HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_FIRST_PARTITION_NUMBER => 1,
                    HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_DEFAULT_PARTITION_NUMBER => 1,
                    HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_ALGO => 'murmur',

                    BasePartitionSettings::FIELD_DB_PARTITION => [
                        HashDbPartitionSettings::FIELD_COUNT => 6,

                        BaseDbPartitionSettings::FIELD_STRATEGY => PartitionDbStrategyEnum::HASH->value,
                        BaseDbPartitionSettings::FIELD_TABLE_SUFFIX_PATTERN => 'part_%s',
                        BaseDbPartitionSettings::FIELD_COLUMN => Transaction::MERCHANT_ID,
                        BaseDbPartitionSettings::FIELD_INDEXES => [
                            [

                            ]
                        ],

                        BaseDbPartitionSettings::FIELD_ADDITIONAL => [
                            BaseDbPartitionSettings::FIELD_STRATEGY => PartitionDbStrategyEnum::RANGE->value,
                            BaseDbPartitionSettings::FIELD_TABLE_SUFFIX_PATTERN => '_%s',
                            BaseDbPartitionSettings::FIELD_COLUMN => Model::CREATED_AT,
                            BaseDbPartitionSettings::FIELD_INDEXES => [
                                DbIndexTypeEnum::BTREE->value => Transaction::MERCHANT_ID,
                            ],

                            RangeDbPartitionSettings::FIELD_RANGE_TYPE => PartitionRangeTypeEnum::TIMESTAMP->value,
                            RangeDbPartitionSettings::FIELD_RANGE_STEP => PartitionRangeStepEnum::MONTH->value,
                            RangeDbPartitionSettings::FIELD_AUTO_CREATE_TABLE => true,
                            RangeDbPartitionSettings::FIELD_AUTO_CREATE_TABLE_COUNT => 2,
                        ],
                    ],
                ],
                PartitionStrategyEnum::DEDICATED->arrayKey() => [
                    // Dedicated table
                    BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => 'transactions_dedicated_merchant_%s',

                    BasePartitionSettings::FIELD_DB_PARTITION => [
                        BaseDbPartitionSettings::FIELD_STRATEGY => PartitionDbStrategyEnum::RANGE->value,
                        BaseDbPartitionSettings::FIELD_TABLE_SUFFIX_PATTERN => '_%s',
                        BaseDbPartitionSettings::FIELD_COLUMN => Model::CREATED_AT,

                        RangeDbPartitionSettings::FIELD_RANGE_TYPE => PartitionRangeTypeEnum::TIMESTAMP->value,
                        RangeDbPartitionSettings::FIELD_RANGE_STEP => PartitionRangeStepEnum::QUARTER->value,
                        RangeDbPartitionSettings::FIELD_AUTO_CREATE_TABLE => true,
                        RangeDbPartitionSettings::FIELD_AUTO_CREATE_TABLE_COUNT => 4,
                    ],
                ],
            ],

        ],
    ],
];