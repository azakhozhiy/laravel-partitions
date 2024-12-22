<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Helper;

class DbTableHelper
{
    public static function buildRegexTablePattern(string $complexPattern): string
    {
        $patternParts = explode('%s', $complexPattern);

        $regexPattern = '';
        $lastIndex = count($patternParts) - 1;

        foreach ($patternParts as $index => $part) {
            $regexPattern .= preg_quote($part, '/');

            if ($index !== $lastIndex) {
                $regexPattern .= '[^_]*';
            } else {
                $regexPattern .= '.*';
            }
        }

        return '^'.$regexPattern.'$';
    }

    public static function buildTableName(string $baseTable, string ...$parts): string
    {
        return $baseTable.'_'.implode('', $parts);
    }
}
