<?php

namespace Oro\Bundle\EntityExtendBundle\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataFactory;

class ExtendClassMetadataFactory extends ClassMetadataFactory
{
    /**
     * {@inheritdoc}
     */
    public function setMetadataFor($className, $class)
    {
        $cacheDriver = $this->getCacheDriver();
        if (null !== $cacheDriver) {
            $cacheDriver->save($className . $this->cacheSalt, $class, null);
        }

        parent::setMetadataFor($className, $class);
    }
}
