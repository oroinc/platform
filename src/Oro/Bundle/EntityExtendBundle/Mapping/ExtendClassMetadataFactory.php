<?php

namespace Oro\Bundle\EntityExtendBundle\Mapping;

use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;

class ExtendClassMetadataFactory extends OroClassMetadataFactory
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
