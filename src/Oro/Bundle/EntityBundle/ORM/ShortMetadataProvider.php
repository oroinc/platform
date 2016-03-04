<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;

class ShortMetadataProvider
{
    const ALL_SHORT_METADATA_CACHE_KEY = 'oro_entity.all_short_metadata';

    /** @var array */
    protected $metadataCache;

    /**
     * Gets short form of metadata for all entities registered in a given entity manager.
     *
     * @param ObjectManager $manager        The entity manager
     * @param bool          $throwException Whether to throw exception in case if metadata cannot be retrieved
     *
     * @return ShortClassMetadata[]
     */
    public function getAllShortMetadata(ObjectManager $manager, $throwException = true)
    {
        if (null === $this->metadataCache) {
            $metadataFactory = $manager->getMetadataFactory();
            $cacheDriver = $metadataFactory instanceof AbstractClassMetadataFactory
                ? $metadataFactory->getCacheDriver()
                : null;
            if ($cacheDriver) {
                $this->metadataCache = $cacheDriver->fetch(static::ALL_SHORT_METADATA_CACHE_KEY);
                if (false === $this->metadataCache) {
                    $this->metadataCache = $this->loadAllShortMetadata($manager, $throwException);
                    $cacheDriver->save(static::ALL_SHORT_METADATA_CACHE_KEY, $this->metadataCache);
                }
            } else {
                $this->metadataCache = $this->loadAllShortMetadata($manager, $throwException);
            }
        }

        return $this->metadataCache;
    }

    /**
     * @param ObjectManager $manager
     * @param bool          $throwException
     *
     * @return ShortClassMetadata[]
     */
    protected function loadAllShortMetadata(ObjectManager $manager, $throwException)
    {
        $result = [];

        $allMetadata = $throwException
            ? $manager->getMetadataFactory()->getAllMetadata()
            : SafeDatabaseChecker::getAllMetadata($manager);
        foreach ($allMetadata as $metadata) {
            $shortMetadata = new ShortClassMetadata($metadata->getName());
            if ($metadata instanceof ClassMetadata && $metadata->isMappedSuperclass) {
                $shortMetadata->isMappedSuperclass = true;
            }

            $result[] = $shortMetadata;
        }

        return $result;
    }
}
