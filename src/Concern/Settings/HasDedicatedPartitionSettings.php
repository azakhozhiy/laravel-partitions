<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Concern\Settings;

trait HasDedicatedPartitionSettings
{
    public function fillByDedicatedConfig(array $config): static
    {
        return $this;
    }
}
