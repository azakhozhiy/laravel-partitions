<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Settings;

use Illuminate\Support\Arr;

trait HasHashViaCodePartitionSettings
{
    public const string FIELD_HASH_VIA_CODE_PARTITION_COUNT = 'hash_via_code_partition_count';
    public const string FIELD_HASH_VIA_CODE_FIRST_PARTITION_NUMBER = 'hash_via_code_first_partition_number';
    public const string FIELD_HASH_VIA_CODE_DEFAULT_PARTITION_NUMBER = 'hash_via_code_default_partition_number';
    public const string FIELD_HASH_VIA_CODE_ALGO = 'hash_via_code_algo';
    public const string FIELD_HASH_VIA_CODE_PARTITION_KEYS_DELIMITER = 'hash_via_code_partition_keys_delimiter';

    protected int $hashViaCodePartitionCount;
    protected int $hashViaCodePartitionFirstNumber = 1;
    protected int $hashViaCodePartitionDefaultNumber = 1;
    protected string $hashViaCodeAlgo = 'murmur';
    protected string $hashViaCodePartitionKeysDelimiter = ':';

    public function fillByHashViaCodeConfig(array $config): static
    {
        $hashViaCodePartitionCount = (int)Arr::get(
            $config,
            static::FIELD_HASH_VIA_CODE_PARTITION_COUNT,
            5
        );

        $hashViaCodeFirstPartitionNumber = (int)Arr::get(
            $config,
            static::FIELD_HASH_VIA_CODE_FIRST_PARTITION_NUMBER,
            1
        );

        $hashViaCodeDefaultPartitionNumber = (int)Arr::get(
            $config,
            static::FIELD_HASH_VIA_CODE_DEFAULT_PARTITION_NUMBER,
            1
        );

        $hashViaCodePartitionKeysDelimiter = (string)Arr::get(
            $config,
            static::FIELD_HASH_VIA_CODE_PARTITION_KEYS_DELIMITER,
            ":"
        );

        $hashViaCodeAlgo = Arr::get($config, static::FIELD_HASH_VIA_CODE_ALGO, 'murmur');

        return $this
            ->setHashViaCodePartitionCount($hashViaCodePartitionCount)
            ->setHashViaCodePartitionFirstNumber($hashViaCodeFirstPartitionNumber)
            ->setHashViaCodePartitionDefaultNumber($hashViaCodeDefaultPartitionNumber)
            ->setHashViaCodeAlgo($hashViaCodeAlgo)
            ->setHashViaCodePartitionKeysDelimiter($hashViaCodePartitionKeysDelimiter);
    }

    public function getHashViaCodePartitionKeysDelimiter(): string
    {
        return $this->hashViaCodePartitionKeysDelimiter;
    }

    public function setHashViaCodePartitionKeysDelimiter(string $value): static
    {
        $this->hashViaCodePartitionKeysDelimiter = $value;

        return $this;
    }

    public function setHashViaCodePartitionCount(int $count): static
    {
        $this->hashViaCodePartitionCount = $count;

        return $this;
    }

    public function setHashViaCodePartitionFirstNumber(int $firstNumber): static
    {
        $this->hashViaCodePartitionFirstNumber = $firstNumber;

        return $this;
    }

    public function setHashViaCodePartitionDefaultNumber(int $defaultNumber): static
    {
        $this->hashViaCodePartitionDefaultNumber = $defaultNumber;

        return $this;
    }

    public function setHashViaCodeAlgo(string $algo): static
    {
        $this->hashViaCodeAlgo = $algo;

        return $this;
    }

    public function getHashViaCodePartitionCount(): int
    {
        return $this->hashViaCodePartitionCount;
    }

    public function getHashViaCodePartitionFirstNumber(): int
    {
        return $this->hashViaCodePartitionFirstNumber;
    }

    public function getHashViaCodePartitionDefaultNumber(): int
    {
        return $this->hashViaCodePartitionDefaultNumber;
    }

    public function getHashViaCodeAlgo(): string
    {
        return $this->hashViaCodeAlgo;
    }
}
