<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Tests\Unit;

use AZakhozhiy\Laravel\Partitions\Database\Blueprint;
use AZakhozhiy\Laravel\Partitions\Database\Schema;
use AZakhozhiy\Laravel\Partitions\Service\ModelMigrationService;
use AZakhozhiy\Laravel\Partitions\Settings\BaseTablePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Settings\HashViaCodePartitionSettings;
use AZakhozhiy\Laravel\Partitions\Tests\TestCase;
use AZakhozhiy\Laravel\Partitions\Tests\TestData\Model\Merchant;
use AZakhozhiy\Laravel\Partitions\Tests\TestData\Model\Transaction;

class CommonTest extends TestCase
{
    public function test_create_all_types_of_partition_tables(): void
    {
        // create merchants and transactions hash via code, transaction base table
        $this->loadMigrationsFrom(__DIR__.'/../TestData/migrations');

        $merchants = Merchant::all();

        $tables = [];
        $tables['merchants'] = 1;

        $transactionsPartitionSettings = Transaction::getPartitionedSettings();
        foreach ($transactionsPartitionSettings->getAll() as $setting) {
            if ($setting instanceof HashViaCodePartitionSettings) {
                foreach ($setting->getAllTablesNames() as $hashViaCodeTable) {
                    $tables[$hashViaCodeTable] = 1;
                }
            } elseif ($setting instanceof BaseTablePartitionSettings) {
                $tables[$setting->getBaseTablePattern()] = 1;
            }

            if($setting->getAllTables()){
                dd($setting->getAllTables());
            }
        }

        /** @var Merchant $merchant */
        foreach ($merchants as $merchant) {
            if ($merchant->getPartitionStrategy()->isDedicated()) {
                $tableDTO = ModelMigrationService::createDedicatedTableBySettings(
                    $merchant,
                    Transaction::class,
                    static function (Blueprint $table) {
                        $table->uuid();
                        $table->uuid(Transaction::MERCHANT_ID);
                        $table->unsignedDecimal('amount', 10, 5)->nullable();
                        $table->timestamps();
                    }
                );

                $tables[$tableDTO->getTableName()] = 1;
                if ($tableDTO->hasDbPartitions()) {
                    foreach ($tableDTO->getDbPartitions() as $tablePartition) {
                        $tables[$tablePartition->getTableName()] = 1;
                    }
                }
            }
        }

        dd($tables);

        $dbTables = Schema::getTables();

        foreach ($dbTables as $dbTable) {
            self::assertArrayHasKey($dbTable['name'], $tables);
        }

        self::assertTrue(true);
    }
}
