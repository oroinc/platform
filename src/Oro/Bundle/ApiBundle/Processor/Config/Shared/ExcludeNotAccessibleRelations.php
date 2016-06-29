<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
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

    /** @var ResourcesProvider */
    protected $resourcesProvider;

    /**
     * @param DoctrineHelper    $doctrineHelper
     * @param ResourcesProvider $resourcesProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ResourcesProvider $resourcesProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->resourcesProvider = $resourcesProvider;
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
        if ($this->resourcesProvider->isResourceAccessible($targetMetadata->name, $version, $requestType)) {
            return true;
        }
        if ($targetMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            // check that at least one inherited entity has Data API resource
            foreach ($targetMetadata->subClasses as $inheritedEntityClass) {
                if ($this->resourcesProvider->isResourceAccessible($inheritedEntityClass, $version, $requestType)) {
                    return true;
                }
            }
        }

        return false;
    }
}
