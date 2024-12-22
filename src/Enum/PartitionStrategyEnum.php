<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Enum;

use RuntimeException;
use AZakhozhiy\Laravel\Partitions\Settings\BasePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\BaseTablePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\DedicatedPartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;

enum PartitionStrategyEnum: string
{
    /** Partitioning by DB with base table */
    case BASE_TABLE = 'BASE_TABLE';

    /** Partitioning by php code and murmur hash */
    case HASH_VIA_CODE = 'HASH_VIA_CODE';

    /** Partitioning by dedicated table (transaction_dedicated_{merchant_id}) */
    case DEDICATED = 'DEDICATED';

    public function arrayKey(): string
    {
        return match ($this) {
            self::BASE_TABLE => 'base_table',
            self::HASH_VIA_CODE => 'hash_via_code',
            self::DEDICATED => 'dedicated',
        };
    }

    public function supportsQueryByPartitionNumber(): bool
    {
        return match ($this) {
            self::DEDICATED, self::BASE_TABLE => false,
            self::HASH_VIA_CODE => true
        };
    }

    public function supportsQueryByPartitionKey(): bool
    {
        return true;
    }

    public static function fromArrayKey(string $key): self
    {
        foreach (self::cases() as $case) {
            if ($case->arrayKey() === $key) {
                return $case;
            }
        }

        throw new RuntimeException("Unknown partition strategy with array key $key.");
    }


    public function isHashViaCode(): bool
    {
        return $this === self::HASH_VIA_CODE;
    }

    public function isBaseTable(): bool
    {
        return $this === self::BASE_TABLE;
    }

    public function isDedicated(): bool
    {
        return $this === self::DEDICATED;
    }

    public function needToPreCreateTables(): bool
    {
        return match ($this) {
            self::BASE_TABLE,
            self::HASH_VIA_CODE => true,

            self::DEDICATED => false
        };
    }

    public static function getNeedToPreCreateTables(): array
    {
        return [
            self::BASE_TABLE,
            self::HASH_VIA_CODE,
        ];
    }

    public function canUseExistingTables(): bool
    {
        return match ($this) {
            self::BASE_TABLE,
            self::HASH_VIA_CODE => true,

            self::DEDICATED => false
        };
    }

    public function canUseWithoutPartitionKeys(): bool
    {
        return match ($this) {
            self::BASE_TABLE, self::HASH_VIA_CODE => true,
            self::DEDICATED => false,
        };
    }

    public function createSettingsObj(array $config): BasePartitionSettings
    {
        /** @var class-string<BasePartitionSettings> $settingsClass */
        $settingsClass = match ($this) {
            self::BASE_TABLE => BaseTablePartitionSettings::class,
            self::HASH_VIA_CODE => HashViaCodePartitionSettings::class,
            self::DEDICATED => DedicatedPartitionSettings::class,
        };

        return $settingsClass::createByDefaultConfig($config);
    }
}
