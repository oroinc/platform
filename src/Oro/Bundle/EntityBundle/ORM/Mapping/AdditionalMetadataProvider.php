<?php

namespace Oro\Bundle\EntityBundle\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Doctrine helper methods.
 */
class AdditionalMetadataProvider
{
    protected ManagerRegistry $registry;
    protected CacheItemPoolInterface $cacheAdapter;

    public function __construct(ManagerRegistry $registry, CacheItemPoolInterface $cacheAdapter)
    {
        $this->registry = $registry;
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    public function getInversedUnidirectionalAssociationMappings($className)
    {
        $cacheKey = $this->createCacheKey($className);
        $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            $this->warmUpMetadata();
            $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        }
        return $cacheItem->isHit() ? $cacheItem->get() : [];
    }

    public function warmUpMetadata()
    {
        $allMetadata = $this->registry->getManager()->getMetadataFactory()->getAllMetadata();
        foreach ($allMetadata as $classMetadata) {
            $cacheKey = $this->createCacheKey($classMetadata->name);
            $poolItem = $this->cacheAdapter->getItem($cacheKey);
            $poolItem->set($this->createInversedUnidirectionalAssociationMappings($classMetadata, $allMetadata));
            $this->cacheAdapter->saveDeferred($poolItem);
        }
        $this->cacheAdapter->commit();
    }

    /**
     * @param ClassMetadata   $classMetadata
     * @param ClassMetadata[] $allMetadata
     *
     * @return array
     */
    protected function createInversedUnidirectionalAssociationMappings(ClassMetadata $classMetadata, array $allMetadata)
    {
        $result = [];
        foreach ($allMetadata as $metadata) {
            if ($metadata == $classMetadata) {
                // Skip own class metadata
                continue;
            }

            $currentClassName = $metadata->getName();
            $associationMappings = $metadata->getAssociationsByTargetClass($classMetadata->name);

            foreach ($associationMappings as $fieldName => $associationMapping) {
                if ((isset($associationMapping['type']) &&
                        $associationMapping['type'] === ClassMetadataInfo::MANY_TO_MANY) ||
                    isset($associationMapping['mappedBy'])
                ) {
                    // Skip "mapped by" and many-to-many as it's included on other side.
                    continue;
                }

                $associationMapping['mappedBySourceEntity'] = false;
                $associationMapping['_generatedFieldName'] = $this->createInverseAssociationFieldName(
                    $currentClassName,
                    $fieldName
                );

                $result[] = $associationMapping;
            }
        }

        return $result;
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     */
    protected function createInverseAssociationFieldName($className, $fieldName)
    {
        return str_replace('\\', '_', $className) . '_' . $fieldName;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function createCacheKey($className)
    {
        return sprintf(
            'oro_entity.additional_metadata.%s',
            UniversalCacheKeyGenerator::normalizeCacheKey($className)
        );
    }
}
