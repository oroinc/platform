<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

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

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
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
