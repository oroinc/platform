<?php

namespace Oro\Bundle\EntityBundle\ORM\Mapping;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class AdditionalMetadataProvider
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var Cache */
    protected $cacheDriver;

    /**
     * @param ManagerRegistry $registry
     * @param Cache $cacheDriver
     */
    public function __construct(ManagerRegistry $registry, Cache $cacheDriver)
    {
        $this->registry = $registry;
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    public function getInversedUnidirectionalAssociationMappings($className)
    {
        $cacheKey = $this->createCacheKey($className);
        if (false === ($result = $this->cacheDriver->fetch($cacheKey))) {
            $this->warmUpMetadata();

            return $this->cacheDriver->fetch($cacheKey);
        }

        return $result;
    }

    public function warmUpMetadata()
    {
        $allMetadata = $this->registry->getManager()->getMetadataFactory()->getAllMetadata();
        foreach ($allMetadata as $classMetadata) {
            $this->cacheDriver->save(
                $this->createCacheKey($classMetadata->name),
                $this->createInversedUnidirectionalAssociationMappings($classMetadata, $allMetadata)
            );
        }
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
        return sprintf('oro_entity.additional_metadata.%s', $className);
    }
}
