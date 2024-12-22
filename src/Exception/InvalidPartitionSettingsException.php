<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Exception;

use RuntimeException;
use Throwable;

class InvalidPartitionSettingsException extends RuntimeException
{
    public function __construct(array $messages = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(static::buildErrorMessage($messages), $code, $previous);
    }

    public static function buildErrorMessage(array $messages = []): string
    {
        $msg = "Building partition settings ended with errors: ";

        foreach ($messages as $message) {
            $msg .= ", $message";
        }

        return $msg;
    }

}
