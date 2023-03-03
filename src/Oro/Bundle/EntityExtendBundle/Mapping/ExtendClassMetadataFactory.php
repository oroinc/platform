<?php

namespace Oro\Bundle\EntityExtendBundle\Mapping;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\ORM\MappingDriverChain;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\Tools\YamlDriver;

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

    /**
     * Sets mapping drivers to read metadata from the cache instead of YAML files
     */
    public function setMappingDriver(EntityManager $entityManager, YamlDriver $entityDriver): void
    {
        /** @var MappingDriverChain $driver */
        $driver = $entityManager->getConfiguration()->getMetadataDriverImpl();
        $driver->addDriver($entityDriver, ExtendClassLoadingUtils::getEntityNamespace());
    }
}
