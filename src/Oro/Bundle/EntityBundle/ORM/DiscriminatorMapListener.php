<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;

/**
 * Handles Doctrine class metadata loading to populate discriminator maps.
 *
 * This listener automatically adds supported entity classes to the discriminator map
 * of single-table inheritance entities. It enables dynamic registration of entity
 * subclasses in the discriminator map during metadata loading.
 */
class DiscriminatorMapListener
{
    /**
     * @var string[]
     */
    protected $supportedClassNames = [];

    /**
     * @param string $key
     * @param string $supportedClassName
     */
    public function addClass($key, $supportedClassName)
    {
        $this->supportedClassNames[$key] = $supportedClassName;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        if (!$this->supportedClassNames) {
            return;
        }

        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (!$metadata->isInheritanceTypeSingleTable()) {
            return;
        }

        if (!$metadata->isRootEntity()) {
            return;
        }

        $className = $metadata->getName();
        foreach ($this->supportedClassNames as $key => $supportedClassName) {
            if (is_a($supportedClassName, $className, true)) {
                $metadata->addDiscriminatorMapClass($key, $supportedClassName);
            }
        }
    }
}
