<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityExtendBundle\EntityExtend\CachedClassUtils;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;

/**
 * Extend entity simple set cache helper.
 */
class ExtendEntityStaticCache
{
    protected static array $ignoreSetCache = [];
    protected static array $ignoreGetCache = [];
    protected static array $methodExists = [];

    public static function allowIgnoreSetCache(EntityFieldProcessTransport $transport): void
    {
        self::$ignoreSetCache[$transport->getClass()][$transport->getName()] = true;
    }

    public static function isAllowedIgnoreSet(object $object, string $property): bool
    {
        return isset(self::$ignoreSetCache[CachedClassUtils::getClass($object)][$property]);
    }

    public static function allowIgnoreGetCache(EntityFieldProcessTransport $transport): void
    {
        self::$ignoreGetCache[$transport->getClass()][$transport->getName()] = true;
    }

    public static function isAllowedIgnoreGet(object $object, string $property): bool
    {
        return isset(self::$ignoreGetCache[CachedClassUtils::getClass($object)][$property]);
    }

    public static function setMethodExistsCache(
        EntityFieldProcessTransport $transport,
        bool $value,
    ): void {
        self::$methodExists[$transport->getClass()][$transport->getName()] = $value;
    }

    public static function getMethodExistsCache(
        EntityFieldProcessTransport $transport,
    ): ?bool {
        if (!isset(self::$methodExists[$transport->getClass()][$transport->getName()])) {
            return null;
        }

        return self::$methodExists[$transport->getClass()][$transport->getName()];
    }
}
