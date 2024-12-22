<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use AZakhozhiy\Laravel\Partitions\Enum\PartitionStrategyEnum;
use AZakhozhiy\Laravel\Partitions\Tests\TestData\Model\Merchant;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('merchants', static function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->string(Merchant::PARTITION_STRATEGY);
            $table->string('name')->unique();
        });

        Merchant::query()->insert($this->baseMerchants());
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
                'name' => 'unlimit',
            ],
        ];
    }
};