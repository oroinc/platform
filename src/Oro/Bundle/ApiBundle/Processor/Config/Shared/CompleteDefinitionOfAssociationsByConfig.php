<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads configuration from the "relations" section of "Resources/config/oro/api.yml"
 * for all associations that were not configured yet.
 */
class CompleteDefinitionOfAssociationsByConfig implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RelationConfigProvider */
    protected $relationConfigProvider;

    /**
     * @param DoctrineHelper         $doctrineHelper
     * @param RelationConfigProvider $relationConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        RelationConfigProvider $relationConfigProvider
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->relationConfigProvider = $relationConfigProvider;
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
    protected function completeEntityAssociations(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $propertyPath => $mapping) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && $definition->getField($fieldName)->hasTargetEntity()) {
                continue;
            }

            $this->completeAssociation(
                $definition,
                $fieldName ?: $propertyPath,
                $mapping['targetEntity'],
                $version,
                $requestType,
                $extras
            );
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $version
     * @param RequestType            $requestType
     * @param ConfigExtraInterface[] $extras
     */
    protected function completeObjectAssociations(
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
    protected function completeAssociation(
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
            if ($targetEntity->isCollapsed()) {
                $field->setCollapsed();
                $targetEntity->setCollapsed(false);
            }
            $field->setTargetEntity($targetEntity);
        }
    }
}
