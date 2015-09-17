<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

class DoctrineListener
{
    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $event->getClassMetadata();
        if (ConfigHelper::isConfigModelEntity($classMetadata->getName())) {
            // all entity config related entities should be read-only
            // in all connections except the 'config' connection
            $classMetadata->markReadOnly();
        }
    }
}
