<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesLoader;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Excludes relations that are pointed to not accessible resources.
 * For example if entity1 has a reference to to entity2, but entity2 does not have Data API resource,
 * the relation will be excluded.
 */
class ExcludeNotAccessibleRelations implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ResourcesLoader */
    protected $resourcesLoader;

    /** @var ResourcesCache */
    protected $resourcesCache;

    /** @var array */
    private $accessibleResources;

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ResourcesLoader $resourcesLoader
     * @param ResourcesCache  $resourcesCache
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ResourcesLoader $resourcesLoader,
        ResourcesCache $resourcesCache
    ) {
        $this->doctrineHelper  = $doctrineHelper;
        $this->resourcesLoader = $resourcesLoader;
        $this->resourcesCache  = $resourcesCache;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->updateRelations($definition, $entityClass, $context->getVersion(), $context->getRequestType());
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $version
     * @param RequestType            $requestType
     */
    protected function updateRelations(
        EntityDefinitionConfig $definition,
        $entityClass,
        $version,
        RequestType $requestType
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields   = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            if (!$metadata->hasAssociation($propertyPath)) {
                continue;
            }

            $mapping        = $metadata->getAssociationMapping($propertyPath);
            $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($mapping['targetEntity']);
            if (!$this->isResourceForRelatedEntityAccessible($targetMetadata, $version, $requestType)) {
                $field->setExcluded();
            }
        }
    }

    /**
     * @param ClassMetadata $targetMetadata
     * @param string        $version
     * @param RequestType   $requestType
     *
     * @return bool
     */
    protected function isResourceForRelatedEntityAccessible(
        ClassMetadata $targetMetadata,
        $version,
        RequestType $requestType
    ) {
        if ($this->isResourceAccessible($targetMetadata->name, $version, $requestType)) {
            return true;
        }
        if ($targetMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            // check that at least one inherited entity has Data API resource
            foreach ($targetMetadata->subClasses as $inheritedEntityClass) {
                if ($this->isResourceAccessible($inheritedEntityClass, $version, $requestType)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return bool
     */
    protected function isResourceAccessible($entityClass, $version, RequestType $requestType)
    {
        if (null === $this->accessibleResources) {
            $accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
            if (null === $accessibleResources) {
                $this->resourcesLoader->getResources($version, $requestType);
                $accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
            }
            $this->accessibleResources = array_fill_keys($accessibleResources, true);
        }

        return isset($this->accessibleResources[$entityClass]);
    }
}
