<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads full configuration of the target entity for associations were requested to expand.
 * For example, in JSON.API the "include" parameter can be used to request related entities.
 */
class ExpandRelatedEntities implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $configProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $configProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if ($definition->isExcludeAll()) {
            // already processed
            return;
        }

        $entityClass = $context->getClassName();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $this->completeEntityAssociations(
                $this->doctrineHelper->getEntityMetadataForClass($entityClass),
                $definition,
                $context->get(ExpandRelatedEntitiesConfigExtra::NAME),
                $context->getVersion(),
                $context->getRequestType(),
                $context->getPropagableExtras()
            );
        } else {
            $this->completeObjectAssociations(
                $definition,
                $context->get(ExpandRelatedEntitiesConfigExtra::NAME),
                $context->getVersion(),
                $context->getRequestType(),
                $context->getPropagableExtras()
            );
        }
    }

    /**
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     * @param string[]               $expandedEntities
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function completeEntityAssociations(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        $expandedEntities,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $associations = $this->splitExpandedEntities($expandedEntities);
        foreach ($associations as $fieldName => $childExpandedEntities) {
            $propertyPath = null;
            if ($definition->hasField($fieldName)) {
                $propertyPath = $definition->getField($fieldName)->getPropertyPath();
            }
            if (!$propertyPath) {
                $propertyPath = $fieldName;
            }
            if (!$metadata->hasAssociation($propertyPath)) {
                continue;
            }

            $this->completeAssociation(
                $definition,
                $fieldName,
                $metadata->getAssociationTargetClass($propertyPath),
                $childExpandedEntities,
                $version,
                $requestType,
                $extras
            );
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string[]               $expandedEntities
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function completeObjectAssociations(
        EntityDefinitionConfig $definition,
        $expandedEntities,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $associations = $this->splitExpandedEntities($expandedEntities);
        foreach ($associations as $fieldName => $childExpandedEntities) {
            $field = $definition->getField($fieldName);
            if (!$field) {
                continue;
            }
            $targetClass = $field->getTargetClass();
            if (!$targetClass) {
                continue;
            }

            $this->completeAssociation(
                $definition,
                $fieldName,
                $targetClass,
                $childExpandedEntities,
                $version,
                $requestType,
                $extras
            );
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     * @param string                 $targetClass
     * @param string[]               $childExpandedEntities
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function completeAssociation(
        EntityDefinitionConfig $definition,
        $fieldName,
        $targetClass,
        $childExpandedEntities,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $targetExtras = $extras;
        if (!empty($childExpandedEntities)) {
            $targetExtras[] = new ExpandRelatedEntitiesConfigExtra($childExpandedEntities);
        }

        $config = $this->configProvider->getConfig(
            $targetClass,
            $version,
            $requestType,
            $targetExtras
        );
        if ($config->hasDefinition()) {
            $targetEntity = $config->getDefinition();
            foreach ($extras as $extra) {
                $sectionName = $extra->getName();
                if ($extra instanceof ConfigExtraSectionInterface && $config->has($sectionName)) {
                    $targetEntity->set($sectionName, $config->get($sectionName));
                }
            }
            $field = $definition->getOrAddField($fieldName);
            if (!$field->getTargetClass()) {
                $field->setTargetClass($targetClass);
            }
            $field->setTargetEntity($targetEntity);
        }
    }

    /**
     * @param string[] $expandedEntities
     *
     * @return array
     */
    protected function splitExpandedEntities($expandedEntities)
    {
        $result = [];
        foreach ($expandedEntities as $expandedEntity) {
            $path = ConfigUtil::explodePropertyPath($expandedEntity);
            if (count($path) === 1) {
                $result[$expandedEntity] = [];
            } else {
                $fieldName = array_shift($path);
                $result[$fieldName][] = implode(ConfigUtil::PATH_DELIMITER, $path);
            }
        }

        return $result;
    }
}
