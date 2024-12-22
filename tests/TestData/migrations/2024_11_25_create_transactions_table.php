<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use AZakhozhiy\Laravel\Partitions\Database\Blueprint;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Service\ModelMigrationService;
use AZakhozhiy\Laravel\Partitions\Tests\TestData\Model\Merchant;
use AZakhozhiy\Laravel\Partitions\Tests\TestData\Model\Transaction;

return new class () extends Migration {
    public function up(): void
    {
        ModelMigrationService::createTablesBySettings(
            Transaction::class,
            static function (Blueprint $table) {
                $table->uuid();
                $table->uuid(Transaction::MERCHANT_ID);
                $table->unsignedInteger(Transaction::PARTITION_NUMBER)->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }

    protected function baseMerchants(): array
    {
        return [
            [
                'uuid' => Str::orderedUuid()->toString(),
                Merchant::PARTITION_STRATEGY => PartitionStrategyEnum::BASE_TABLE->value,
                'name' => 'merchant1',
            ],
            [
                'uuid' => Str::orderedUuid()->toString(),
                Merchant::PARTITION_STRATEGY => PartitionStrategyEnum::HASH_VIA_CODE->value,
                'name' => 'merchant_hash_via_code',
            ],
            [
                'uuid' => Str::orderedUuid()->toString(),
                Merchant::PARTITION_STRATEGY => PartitionStrategyEnum::DEDICATED->value,
                'name' => 'merchant_dedicated',
            ],
        ];
    }
};