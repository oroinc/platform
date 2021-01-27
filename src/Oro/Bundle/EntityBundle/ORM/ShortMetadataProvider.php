<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;

/**
 * Provides a brief information about manageable entities that can be used to check the following
 * with minimal performance impact:
 * * whether a manageable entity is a final entity or a mapped superclass
 * * whether a manageable entity has at least one association
 * @see \Oro\Bundle\EntityBundle\ORM\ShortClassMetadata
 */
class ShortMetadataProvider
{
    private const ALL_SHORT_METADATA_CACHE_KEY = 'oro_entity.all_short_metadata';

    /** @var ShortClassMetadata[]|null */
    private $metadataCache;

    /**
     * Gets a brief information about manageable entities registered in a given entity manager.
     *
     * @param ObjectManager $manager        The entity manager
     * @param bool          $throwException Whether to throw exception in case if metadata cannot be retrieved
     *
     * @return ShortClassMetadata[] A brief information about manageable entities sorted by entity names
     */
    public function getAllShortMetadata(ObjectManager $manager, $throwException = true): array
    {
        if (null === $this->metadataCache) {
            $metadataFactory = $manager->getMetadataFactory();
            $cacheDriver = $metadataFactory instanceof AbstractClassMetadataFactory
                ? $metadataFactory->getCacheDriver()
                : null;
            if ($cacheDriver) {
                $metadataCache = $cacheDriver->fetch(static::ALL_SHORT_METADATA_CACHE_KEY);
                if (false === $metadataCache) {
                    $metadataCache = $this->loadAllShortMetadata($manager, $throwException);
                    if (null !== $metadataCache) {
                        $cacheDriver->save(static::ALL_SHORT_METADATA_CACHE_KEY, $metadataCache);
                    }
                }
                $this->metadataCache = $metadataCache;
            } else {
                $this->metadataCache = $this->loadAllShortMetadata($manager, $throwException);
            }
        }

        return $this->metadataCache ?? [];
    }

    /**
     * @param ObjectManager $manager
     * @param bool          $throwException
     *
     * @return ShortClassMetadata[]|null
     */
    private function loadAllShortMetadata(ObjectManager $manager, bool $throwException): ?array
    {
        if ($throwException) {
            $allMetadata = $manager->getMetadataFactory()->getAllMetadata();
        } else {
            $allMetadata = SafeDatabaseChecker::safeDatabaseExtendCallable(function () use ($manager) {
                return $manager->getMetadataFactory()->getAllMetadata();
            });
            if (null === $allMetadata) {
                return null;
            }
        }

        $result = [];
        foreach ($allMetadata as $metadata) {
            $entityClass = $metadata->getName();
            $isMappedSuperclass = false;
            $hasAssociations = false;
            if ($metadata instanceof ClassMetadata) {
                $isMappedSuperclass = $metadata->isMappedSuperclass;
                $hasAssociations = count($metadata->getAssociationMappings()) > 0;
            }
            $result[$entityClass] = new ShortClassMetadata($entityClass, $isMappedSuperclass, $hasAssociations);
        }
        ksort($result);
        $result = array_values($result);

        return $result;
    }
}
