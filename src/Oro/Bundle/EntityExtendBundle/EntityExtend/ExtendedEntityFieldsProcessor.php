<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;

/**
 * Processes Entity Field Extensions for Extended Entity
 */
class ExtendedEntityFieldsProcessor
{
    protected static EntityFieldIteratorInterface $iterator;
    protected static ExtendEntityMetadataProviderInterface $metadataProvider;

    public static function initialize(
        EntityFieldIteratorInterface          $iterator,
        ExtendEntityMetadataProviderInterface $metadataProvider
    ): void {
        self::$iterator = $iterator;
        self::$metadataProvider = $metadataProvider;
    }

    public static function executeGet(EntityFieldProcessTransport $transport): void
    {
        self::extendTransportWithMetadataProvider($transport);
        foreach (self::$iterator->getExtensions() as $extension) {
            $extension->get($transport);
            if ($transport->isProcessed()) {
                return;
            }
        }
    }

    public static function executeSet(EntityFieldProcessTransport $transport): void
    {
        self::extendTransportWithMetadataProvider($transport);
        foreach (self::$iterator->getExtensions() as $extension) {
            $extension->set($transport);
            if ($transport->isProcessed()) {
                return;
            }
        }
    }

    public static function executeIsset(EntityFieldProcessTransport $transport): void
    {
        self::extendTransportWithMetadataProvider($transport);
        foreach (self::$iterator->getExtensions() as $extension) {
            $extension->isset($transport);
            if ($transport->isProcessed()) {
                return;
            }
        }
    }

    public static function executeCall(EntityFieldProcessTransport $transport): void
    {
        self::extendTransportWithMetadataProvider($transport);
        foreach (self::$iterator->getExtensions() as $extension) {
            $extension->call($transport);
            if ($transport->isProcessed()) {
                return;
            }
        }
    }

    public static function executePropertyExists(EntityFieldProcessTransport $transport): void
    {
        self::extendTransportWithMetadataProvider($transport);
        foreach (self::$iterator->getExtensions() as $extension) {
            $extension->propertyExists($transport);
            if ($transport->isProcessed()) {
                return;
            }
        }
    }

    public static function executeMethodExists(EntityFieldProcessTransport $transport): void
    {
        $cacheIsMethodExists = ExtendEntityStaticCache::getMethodExistsCache($transport);
        if (null !== $cacheIsMethodExists) {
            if (false === $cacheIsMethodExists) {
                return;
            }
            $transport->setResult(true);
            $transport->setProcessed(true);

            return;
        }
        self::extendTransportWithMetadataProvider($transport);
        foreach (self::$iterator->getExtensions() as $extension) {
            $extension->methodExists($transport);
            if ($transport->isProcessed()) {
                return;
            }
        }
        ExtendEntityStaticCache::setMethodExistsCache($transport, false);
    }

    private static function extendTransportWithMetadataProvider(EntityFieldProcessTransport $transport): void
    {
        if ($transport->getObject()) {
            $transport->setClass($transport->getObject()::class);
        }

        $transport->setEntityMetadataProvider(self::$metadataProvider);
    }

    public static function getMethods(EntityFieldProcessTransport $transport): array
    {
        self::extendTransportWithMetadataProvider($transport);
        $methods = [];
        foreach (self::$iterator->getExtensions() as $extension) {
            $methods += $extension->getMethods($transport);
        }

        return $methods;
    }

    public static function getEntityMetadata(object|string $objectOrClass): ?ConfigInterface
    {
        if (is_object($objectOrClass)) {
            $objectOrClass = CachedClassUtils::getClass($objectOrClass);
        }

        return self::$metadataProvider->getExtendEntityMetadata($objectOrClass);
    }
}
