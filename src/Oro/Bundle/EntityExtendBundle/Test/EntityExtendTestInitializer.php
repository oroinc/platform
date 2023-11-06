<?php

namespace Oro\Bundle\EntityExtendBundle\Test;

use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtensionInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;

/**
 * Initializes {@see ExtendedEntityFieldsProcessor} for unit tests.
 */
class EntityExtendTestInitializer
{
    private static ?EntityFieldTestIterator $entityFieldIterator = null;

    public static function initialize(): void
    {
        if (null !== self::$entityFieldIterator) {
            // already initialized
            return;
        }

        self::$entityFieldIterator = new EntityFieldTestIterator();
        ExtendedEntityFieldsProcessor::initialize(self::$entityFieldIterator, new ExtendEntityTestMetadataProvider());
    }

    public static function addExtension(EntityFieldExtensionInterface $extension): void
    {
        self::assertInitialized();
        self::$entityFieldIterator->addExtension($extension);
    }

    public static function removeExtension(EntityFieldExtensionInterface $extension): void
    {
        self::assertInitialized();
        self::$entityFieldIterator->removeExtension($extension);
    }

    private static function assertInitialized(): void
    {
        if (null === self::$entityFieldIterator) {
            throw new \LogicException(
                'The ExtendedEntityFieldsProcessor is not initialized yet. Call initialize() method before.'
            );
        }
    }
}
