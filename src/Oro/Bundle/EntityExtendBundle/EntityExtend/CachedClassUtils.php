<?php

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Doctrine\Common\Util\ClassUtils;

/**
 * Cached base methods of Doctrine\Common\Util\ClassUtils::getRealClass.
 */
class CachedClassUtils
{
    protected static $realClasses = [];

    public static function getRealClass($className): string
    {
        if (!isset(self::$realClasses[$className])) {
            self::$realClasses[$className] = ClassUtils::getRealClass($className);
        }

        return self::$realClasses[$className];
    }

    public static function getClass($object): string
    {
        return self::getRealClass(get_class($object));
    }
}
