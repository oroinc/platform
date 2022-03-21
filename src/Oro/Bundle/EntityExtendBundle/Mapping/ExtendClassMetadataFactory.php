<?php

namespace Oro\Bundle\EntityExtendBundle\Mapping;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;

/**
 * Caches metadata descriptor for specific class
 */
class ExtendClassMetadataFactory extends OroClassMetadataFactory
{
    /**
     * {@inheritdoc}
     */
    public function setMetadataFor($className, $class)
    {
        $cacheDriver = $this->getCache();
        if (null !== $cacheDriver) {
            $cacheItem = $cacheDriver->getItem(
                UniversalCacheKeyGenerator::normalizeCacheKey($className . $this->cacheSalt)
            );
            $cacheDriver->save($cacheItem->set($class));
        }

        parent::setMetadataFor($className, $class);
    }
}
