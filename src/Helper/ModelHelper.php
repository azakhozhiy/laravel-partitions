<?php

declare(strict_types=1);

namespace AZakhozhiy\Laravel\Partitions\Helper;

use AZakhozhiy\Laravel\Partitions\Concern\Composite\HasCompositePrimaryKey;

class ModelHelper
{
    private static array $traitsCache = [];

    public static function isCompositePrimaryKey(object $model): bool
    {
        $className = $model::class;
        if (!isset(self::$traitsCache[$className])) {
            self::$traitsCache[$className] = class_uses($model);
        }

        if (in_array(HasCompositePrimaryKey::class, self::$traitsCache[$className], true)) {
            /** @var HasCompositePrimaryKey $model */
            return $model->isCompositePrimaryKey();
        }

        return false;
    }
}
