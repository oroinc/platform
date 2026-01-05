<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend\Extension;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProviderInterface;
use Oro\Bundle\EntityExtendBundle\Model\ExtendEntityStorage;

/**
 * Common create entity extend transport functionality.
 */
trait EntityExtendTransportTrait
{
    protected function createTransport(string|object $classOrObject): EntityFieldProcessTransport
    {
        $transport = new EntityFieldProcessTransport();
        if (is_object($classOrObject)) {
            $transport->setObject($classOrObject);
            $transport->setStorage($classOrObject->getStorage());
        }
        $transport->setClass(is_object($classOrObject) ? $classOrObject::class : $classOrObject);
        $transport->setEntityMetadataProvider(
            self::getContainer()->get('oro_entity_extend.entity_metadata_provider')
        );

        return $transport;
    }

    protected function getEmptyStorage(): ExtendEntityStorage
    {
        return new ExtendEntityStorage(
            [],
            \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
        );
    }

    protected function getEmptyMetadataProvider(): ExtendEntityMetadataProviderInterface
    {
        return new class () implements ExtendEntityMetadataProviderInterface {
            public function getExtendEntityMetadata(string $class): ?ConfigInterface
            {
                return null;
            }

            public function getExtendEntityFieldsMetadata(string $class): array
            {
                return [];
            }
        };
    }
}
