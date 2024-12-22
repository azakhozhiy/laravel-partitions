<?php

use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Settings\BasePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\DedicatedPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;

return [
    'strategies' => [
        'settings' => [
            PartitionStrategyEnum::BASE_TABLE->arrayKey() => [
                BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => null,
            ],
            PartitionStrategyEnum::HASH_VIA_CODE->arrayKey() => [
                BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => null,

                // Hash via code
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_PARTITION_COUNT => 5,
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_FIRST_PARTITION_NUMBER => 1,
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_DEFAULT_PARTITION_NUMBER => 1,
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_ALGO => 'murmur',
                HashViaCodePartitionSettings::FIELD_HASH_VIA_CODE_PARTITION_KEYS_DELIMITER => ':',
            ],
            PartitionStrategyEnum::DEDICATED->arrayKey() => [
                BasePartitionSettings::FIELD_BASE_TABLE_PATTERN => null,
            ],
        ],
    ],
];