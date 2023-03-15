<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;

/**
 * Extend entity simple set cache helper.
 */
class ExtendEntityStaticCache
{
    protected static array $ignoreSetCache = [];
    protected static array $ignoreGetCache = [];

    public static function allowIgnoreSetCache(EntityFieldProcessTransport $transport): void
    {
        self::$ignoreSetCache[$transport->getClass()][$transport->getName()] = true;
    }

    public static function isAllowedIgnoreSet(object $object, string $property): bool
    {
        return isset(self::$ignoreSetCache[ClassUtils::getClass($object)][$property]);
    }

    public static function allowIgnoreGetCache(EntityFieldProcessTransport $transport): void
    {
        self::$ignoreGetCache[$transport->getClass()][$transport->getName()] = true;
    }

    public static function isAllowedIgnoreGet(object $object, string $property): bool
    {
        return isset(self::$ignoreGetCache[ClassUtils::getClass($object)][$property]);
    }
}
