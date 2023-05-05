<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteCustomDataTypeHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads full configuration of the target entity for associations were requested to expand.
 * For example, in JSON:API the "include" filter can be used to request related entities.
 */
class ExpandRelatedEntities implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ConfigProvider $configProvider;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;
    private CompleteCustomDataTypeHelper $customDataTypeHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry,
        CompleteCustomDataTypeHelper $customDataTypeHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
        $this->customDataTypeHelper = $customDataTypeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if ($context->isProcessed(CompleteDefinition::OPERATION_NAME)) {
            // this processor must be executed before the entity configuration is completed
            return;
        }

        $entityClass = $context->getClassName();
        $definition = $context->getResult();
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function completeEntityAssociations(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        array $expandedEntities,
        string $version,
        RequestType $requestType,
        array $extras
    ): void {
        $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider($requestType);
        $associations = $this->splitExpandedEntities($expandedEntities);
        foreach ($associations as $fieldName => $targetExpandedEntities) {
            if (!$definition->hasField($fieldName)
                && null !== $definition->findFieldNameByPropertyPath($fieldName)
            ) {
                continue;
            }

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
                $targetClass = $this->getAssociationTargetClass(
                    $targetMetadata,
                    $targetFieldName,
                    $entityOverrideProvider
                );
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
                        ConfigUtil::getAssociationTargetType(
                            $targetMetadata->isCollectionValuedAssociation($targetFieldName)
                        )
                    );
                }
            } elseif ($definition->hasField($fieldName)) {
                $field = $definition->getField($fieldName);
                $targetClass = $field->getTargetClass();
                if ($targetClass) {
                    $dataType = $field->getDataType();
                    if ($dataType) {
                        $this->customDataTypeHelper->completeCustomDataType(
                            $definition,
                            $metadata,
                            $fieldName,
                            $field,
                            $dataType,
                            $version,
                            $requestType
                        );
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
        array $expandedEntities,
        string $version,
        RequestType $requestType,
        array $extras
    ): void {
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
        string $fieldName,
        string $targetClass,
        array $targetExpandedEntities,
        string $version,
        RequestType $requestType,
        array $extras
    ): void {
        if (!empty($targetExpandedEntities)) {
            $extras[] = new ExpandRelatedEntitiesConfigExtra($targetExpandedEntities);
        }

        $config = $this->configProvider->getConfig($targetClass, $version, $requestType, $extras);
        if ($config->hasDefinition()) {
            $targetEntity = $config->getDefinition();
            foreach ($extras as $extra) {
                if ($extra instanceof ConfigExtraSectionInterface) {
                    $sectionName = $extra->getName();
                    if ($config->has($sectionName)) {
                        $targetEntity->set($sectionName, $config->get($sectionName));
                    }
                }
            }
            $field = $definition->getOrAddField($fieldName);
            if (!$field->getTargetClass()) {
                $field->setTargetClass($targetClass);
            }
            $this->mergeAssociationTargetEntity($field, $targetEntity);
        }
    }

    private function mergeAssociationTargetEntity(
        EntityDefinitionFieldConfig $field,
        EntityDefinitionConfig $targetEntity
    ): void {
        $existingTargetEntity = $field->getTargetEntity();
        if (null !== $existingTargetEntity) {
            if ($existingTargetEntity->hasMaxResults()) {
                $targetEntity->setMaxResults($existingTargetEntity->getMaxResults());
            }
            if ($existingTargetEntity->hasOrderBy()) {
                $targetEntity->setOrderBy($existingTargetEntity->getOrderBy());
            }
        }
        $field->setTargetEntity($targetEntity);
    }

    /**
     * @param string[] $expandedEntities
     *
     * @return array
     */
    private function splitExpandedEntities(array $expandedEntities): array
    {
        $result = [];
        foreach ($expandedEntities as $expandedEntity) {
            $path = ConfigUtil::explodePropertyPath($expandedEntity);
            if (\count($path) === 1) {
                $result[$expandedEntity] = [];
            } else {
                $fieldName = array_shift($path);
                $result[$fieldName][] = implode(ConfigUtil::PATH_DELIMITER, $path);
            }
        }

        return $result;
    }

    private function getPropertyPath(string $fieldName, EntityDefinitionConfig $definition): string
    {
        if (!$definition->hasField($fieldName)) {
            return $fieldName;
        }

        return $definition->getField($fieldName)->getPropertyPath($fieldName);
    }

    private function getAssociationTargetClass(
        ClassMetadata $parentMetadata,
        string $associationName,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): string {
        $entityClass = $parentMetadata->getAssociationTargetClass($associationName);
        // use EntityIdentifier as a target class for associations based on Doctrine's inheritance mapping
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        if (!$metadata->isInheritanceTypeNone()) {
            return EntityIdentifier::class;
        }

        return $this->resolveEntityClass($entityClass, $entityOverrideProvider);
    }

    private function resolveEntityClass(
        string $entityClass,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): string {
        $substituteEntityClass = $entityOverrideProvider->getSubstituteEntityClass($entityClass);
        if ($substituteEntityClass) {
            return $substituteEntityClass;
        }

        return $entityClass;
    }
}
