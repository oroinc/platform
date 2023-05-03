<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle;

use Oro\Bundle\EntityExtendBundle\Decorator\OroPropertyAccessorBuilder;
use Oro\Bundle\EntityExtendBundle\Extend\ReflectionExtractor;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Single point to property access builder
 */
final class PropertyAccess
{
    protected static $cacheItemPool = null;

    /**
     * Creates a property accessor with the default configuration.
     */
    public static function createPropertyAccessor(): PropertyAccessorInterface
    {
        return self::createPropertyAccessorBuilder()->getPropertyAccessor();
    }

    public static function createPropertyAccessorWithDotSyntax(
        int $magicMethods = null,
        int $throw = null
    ): PropertyAccessorInterface {
        return self::createPropertyAccessorBuilder()->getPropertyAccessorWithDotArraySyntax($magicMethods, $throw);
    }

    public static function createPropertyAccessorBuilder(): PropertyAccessorBuilder
    {
        $builder = new OroPropertyAccessorBuilder();
        $builder->setReadInfoExtractor(new ReflectionExtractor(enableConstructorExtraction: false));
        $builder->setWriteInfoExtractor(new ReflectionExtractor(mutatorPrefixes: ['set']));
        if (null !== self::$cacheItemPool) {
            $builder->setCacheItemPool(self::$cacheItemPool);
        }

        return $builder;
    }

    public function setCacheItemPool($cacheItemPool): void
    {
        self::$cacheItemPool = $cacheItemPool;
    }
}
