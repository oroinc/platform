<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads full configuration of the target entity for associations were requested to expand.
 * For example, in JSON.API the "include" filter can be used to request related entities.
 */
class ExpandRelatedEntities implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /**
     * @param DoctrineHelper                 $doctrineHelper
     * @param ConfigProvider                 $configProvider
     * @param EntityOverrideProviderRegistry $entityOverrideProviderRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
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
        if (!$definition->isInclusionEnabled()) {
            throw new NotSupportedConfigOperationException($entityClass, ExpandRelatedEntitiesConfigExtra::NAME);
        }

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
    private function completeEntityAssociations(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        $expandedEntities,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider($requestType);
        $associations = $this->splitExpandedEntities($expandedEntities);
        foreach ($associations as $fieldName => $targetExpandedEntities) {
            $propertyPath = $this->getPropertyPath($fieldName, $definition);

            $lastDelimiter = strrpos($propertyPath, '.');
            if (false === $lastDelimiter) {
                $targetMetadata = $metadata;
                $targetFieldName = $propertyPath;
            } else {
                $targetMetadata = $this->doctrineHelper->findEntityMetadataByPath(
                    $metadata->name,
                    substr($propertyPath, 0, $lastDelimiter)
                );
                $targetFieldName = substr($propertyPath, $lastDelimiter + 1);
            }

            if (null !== $targetMetadata && $targetMetadata->hasAssociation($targetFieldName)) {
                $targetClass = $targetMetadata->getAssociationTargetClass($targetFieldName);
                $substituteTargetClass = $entityOverrideProvider->getSubstituteEntityClass($targetClass);
                if ($substituteTargetClass) {
                    $targetClass = $substituteTargetClass;
                }
                $this->completeAssociation(
                    $definition,
                    $fieldName,
                    $targetClass,
                    $targetExpandedEntities,
                    $version,
                    $requestType,
                    $extras
                );
                $field = $definition->getField($fieldName);
                if (null !== $field && $field->getTargetClass()) {
                    $field->setTargetType(
                        $this->getAssociationTargetType(
                            $targetMetadata->isCollectionValuedAssociation($targetFieldName)
                        )
                    );
                }
            } elseif ($definition->hasField($fieldName)) {
                $field = $definition->getField($fieldName);
                $targetClass = $field->getTargetClass();
                if ($targetClass && $field->hasTargetType() && !$field->hasDataType()) {
                    $this->completeAssociation(
                        $definition,
                        $fieldName,
                        $targetClass,
                        $targetExpandedEntities,
                        $version,
                        $requestType,
                        $extras
                    );
                }
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string[]               $expandedEntities
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    private function completeObjectAssociations(
        EntityDefinitionConfig $definition,
        $expandedEntities,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $associations = $this->splitExpandedEntities($expandedEntities);
        foreach ($associations as $fieldName => $targetExpandedEntities) {
            $field = $definition->getField($fieldName);
            if (null !== $field) {
                $targetClass = $field->getTargetClass();
                if ($targetClass) {
                    $this->completeAssociation(
                        $definition,
                        $fieldName,
                        $targetClass,
                        $targetExpandedEntities,
                        $version,
                        $requestType,
                        $extras
                    );
                }
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     * @param string                 $targetClass
     * @param string[]               $targetExpandedEntities
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    private function completeAssociation(
        EntityDefinitionConfig $definition,
        $fieldName,
        $targetClass,
        $targetExpandedEntities,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        if (!empty($targetExpandedEntities)) {
            $extras[] = new ExpandRelatedEntitiesConfigExtra($targetExpandedEntities);
        }

        $config = $this->configProvider->getConfig($targetClass, $version, $requestType, $extras);
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
    private function splitExpandedEntities($expandedEntities)
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

    /**
     * @param bool $isCollection
     *
     * @return string
     */
    private function getAssociationTargetType($isCollection)
    {
        return $isCollection ? 'to-many' : 'to-one';
    }

    /**
     * @param string                 $fieldName
     * @param EntityDefinitionConfig $definition
     *
     * @return string
     */
    private function getPropertyPath($fieldName, EntityDefinitionConfig $definition)
    {
        if (!$definition->hasField($fieldName)) {
            return $fieldName;
        }

        return $definition->getField($fieldName)->getPropertyPath($fieldName);
    }
}
