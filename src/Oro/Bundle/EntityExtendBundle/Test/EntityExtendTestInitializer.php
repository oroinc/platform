<?php

namespace Oro\Bundle\EntityExtendBundle\Test;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldIteratorInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProviderInterface;

/**
 * Initializes ExtendedEntityFieldsProcessor with the EntityFieldIteratorInterface mock for Unit tests
 */
class EntityExtendTestInitializer
{
    /**
     * @return void
     */
    public static function initialize(): void
    {
        $entityMetadataProvider = new class implements ExtendEntityMetadataProviderInterface {
            public function getExtendEntityMetadata(string $class): ?ConfigInterface
            {
                return null;
            }

            public function getExtendEntityFieldsMetadata(string $class): array
            {
                return [];
            }
        };
        $entityFieldIterator = new class implements EntityFieldIteratorInterface {
            public function getExtensions(): iterable
            {
                return [];
            }
        };
        ExtendedEntityFieldsProcessor::initialize($entityFieldIterator, $entityMetadataProvider);
    }
}
