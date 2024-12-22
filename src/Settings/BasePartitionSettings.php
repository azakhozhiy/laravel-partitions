<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Settings;

use Illuminate\Support\Arr;
use Throwable;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Exception\InvalidPartitionSettingsException;
use AZakhozhiy\Laravel\Partitions\Helper\DbPartitionHelper;
use AZakhozhiy\Laravel\Partitions\Helper\DbTableHelper;
use AZakhozhiy\Laravel\Partitions\Service\DbPartitionSettingsFactory;
use AZakhozhiy\Laravel\Partitions\Settings\Database\BaseDbPartitionSettings;

abstract class BasePartitionSettings
{
    public const string FIELD_BASE_TABLE_PATTERN = 'base_table_pattern';
    public const string FIELD_DB_PARTITION = 'db_partition';

    protected ?string $baseTablePattern = null;
    protected ?array $allTables = null;

    protected ?BaseDbPartitionSettings $dbPartition = null;

    abstract public static function createByDefaultConfig(array $config): static;

    abstract public static function getPartitionStrategyEnum(): PartitionStrategyEnum;

    abstract public function getTableNameByPartitionKeys(string|int ...$keys): string;

    abstract public function getTableNameByPartitionNumber(int $number): string;

    abstract public function getDefaultTableName(): string;

    public function hasDbPartition(): bool
    {
        return $this->dbPartition !== null;
    }

    public function getDbPartition(): ?BaseDbPartitionSettings
    {
        return $this->dbPartition;
    }

    public function hasAllTables(): bool
    {
        return $this->allTables !== null;
    }

    public function getAllTables(): array
    {
        if($this->allTables === null){
            $this->buildAllTables();
        }

        return $this->allTables;
    }

    public function addDbPartition(BaseDbPartitionSettings $dbPartitionSettings): static
    {
        $this->dbPartition = $dbPartitionSettings;

        return $this;
    }

    public function fillByBaseConfig(array $config): static
    {
        $baseTablePattern = Arr::get($config, static::FIELD_BASE_TABLE_PATTERN);

        $this->setBaseTablePattern($baseTablePattern);

        if (isset($config[static::FIELD_DB_PARTITION])) {
            $dbPartition = DbPartitionSettingsFactory::fromConfig($config[static::FIELD_DB_PARTITION]);
            $this->addDbPartition($dbPartition);
        }

        return $this;
    }

    public static function supportsQueryByPartitionNumber(): bool
    {
        return static::getPartitionStrategyEnum()->supportsQueryByPartitionNumber();
    }

    public static function supportsQueryByPartitionKey(): bool
    {
        return static::getPartitionStrategyEnum()->supportsQueryByPartitionKey();
    }

    public function setBaseTablePattern(?string $tablePattern): static
    {
        $this->baseTablePattern = $tablePattern;

        return $this;
    }

    public function ensureValidState(): void
    {
        $className = static::class;
        $errors = [];
        foreach ($this as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            if ($key === 'dbPartition' || $key === 'allTables') {
                continue;
            }

            if ($this->{$key} === null) {
                $errors[] = "$key can not be a null for $className class.";
            }
        }

        if (!empty($errors)) {
            throw new InvalidPartitionSettingsException($errors);
        }
    }

    public function buildAllTables(): array
    {
        if ($this->allTables) {
            return $this->allTables;
        }

        $tables = [];
        if ($this instanceof HashViaCodePartitionSettings) {
            $tables = $this->getAllTablesNames();
        }

        if ($this instanceof BaseTablePartitionSettings) {
            $tables[] = $this->getBaseTablePattern();
        }

        if ($this->getDbPartition()) {
            foreach ($tables as $table) {
                $pattern = DbTableHelper::buildTableName($table, $this->getDbPartition()->getTableSuffixPattern());
                $pattern = DbTableHelper::buildRegexTablePattern($pattern);
                $tables = array_merge($tables, DbPartitionHelper::getAllPartitionsByRegexPattern($pattern));
            }
        }

        $this->allTables = $tables;

        return $tables;
    }

    public function getBaseTablePattern(): ?string
    {
        return $this->baseTablePattern;
    }
}
