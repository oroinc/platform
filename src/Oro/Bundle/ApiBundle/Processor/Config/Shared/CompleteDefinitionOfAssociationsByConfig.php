<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads configuration from the "relations" section of "Resources/config/oro/api.yml"
 * for all associations that were not configured yet.
 */
class CompleteDefinitionOfAssociationsByConfig implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var RelationConfigProvider */
    private $relationConfigProvider;

    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /**
     * @param DoctrineHelper                 $doctrineHelper
     * @param RelationConfigProvider         $relationConfigProvider
     * @param EntityOverrideProviderRegistry $entityOverrideProviderRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        RelationConfigProvider $relationConfigProvider,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->relationConfigProvider = $relationConfigProvider;
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
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $this->completeEntityAssociations(
                $this->doctrineHelper->getEntityMetadataForClass($entityClass),
                $definition,
                $context->getVersion(),
                $context->getRequestType(),
                $context->getPropagableExtras()
            );
        } else {
            $this->completeObjectAssociations(
                $definition,
                $context->getVersion(),
                $context->getRequestType(),
                $context->getPropagableExtras()
            );
        }
    }

    /**
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    private function completeEntityAssociations(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider($requestType);
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $propertyPath => $mapping) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && $definition->getField($fieldName)->hasTargetEntity()) {
                continue;
            }

            $targetClass = $mapping['targetEntity'];
            $substituteTargetClass = $entityOverrideProvider->getSubstituteEntityClass($targetClass);
            if ($substituteTargetClass) {
                $targetClass = $substituteTargetClass;
            }
            if (!$fieldName) {
                $fieldName = $propertyPath;
            }
            $this->completeAssociation($definition, $fieldName, $targetClass, $version, $requestType, $extras);
            $field = $definition->getField($fieldName);
            if (null !== $field && $field->getTargetClass()) {
                $field->setTargetType(
                    $this->getAssociationTargetType(!($mapping['type'] & ClassMetadata::TO_ONE))
                );
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    private function completeObjectAssociations(
        EntityDefinitionConfig $definition,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->hasTargetEntity()) {
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
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    private function completeAssociation(
        EntityDefinitionConfig $definition,
        $fieldName,
        $targetClass,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $config = $this->relationConfigProvider->getRelationConfig(
            $targetClass,
            $version,
            $requestType,
            $extras
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
            if ($targetEntity->isCollapsed()) {
                $field->setCollapsed();
                $targetEntity->setCollapsed(false);
            }
            $field->setTargetEntity($targetEntity);
        }
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
}
