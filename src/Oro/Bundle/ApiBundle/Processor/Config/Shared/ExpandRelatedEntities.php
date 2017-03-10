<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

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
        array $expandedEntities,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $associations = $this->splitExpandedEntities($expandedEntities);
        foreach ($associations as $fieldName => $childExpandedEntities) {
            $propertyPath = $fieldName;
            if ($definition->hasField($fieldName)) {
                $propertyPath = $definition->getField($fieldName)->getPropertyPath() ?: $propertyPath;
            }

            $associationChain = ConfigUtil::explodePropertyPath($propertyPath);
            if (count($associationChain) > 1) {
                $childExpandedEntities = [$propertyPath];
            }

            $associationData = $this->getAssociationData($metadata, $associationChain);
            if (!$associationData) {
                continue;
            }

            $this->completeAssociation(
                $definition,
                $fieldName,
                $associationData['targetClass'],
                $childExpandedEntities,
                $version,
                $requestType,
                $extras
            );
            $field = $definition->getField($fieldName);
            if (null !== $field && $field->getTargetClass()) {
                $field->setTargetType(
                    $this->getAssociationTargetType($associationData['isCollection'])
                );
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param array $associationChain
     *
     * @return array|false
     * [
     *     'isCollection' => bool,
     *     'targetClass' => string,
     * ]
     */
    protected function getAssociationData(ClassMetadata $metadata, array $associationChain)
    {
        $currentMetadata = $metadata;
        $associationTargetClass = null;
        $isCollection = false;
        $pathLength = count($associationChain);
        for ($i = 0; $i < $pathLength; $i++) {
            if (!$currentMetadata->hasAssociation($associationChain[$i])) {
                return false;
            }

            $associationTargetClass = $currentMetadata->getAssociationTargetClass($associationChain[$i]);
            $isCollection = $isCollection || $currentMetadata->isCollectionValuedAssociation($associationChain[$i]);
            if ($i !== ($pathLength - 1)) {
                $currentMetadata = $this->doctrineHelper->getEntityMetadataForClass($associationTargetClass);
            }
        }

        return [
            'isCollection' => $isCollection,
            'targetClass' => $associationTargetClass,
        ];
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

            if ($field->hasPropertyPath()) {
                $this->updateRelatedFieldTargetEntity(
                    $definition,
                    $field,
                    $targetEntity
                );
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param EntityDefinitionFieldConfig $field
     * @param EntityDefinitionConfig $targetEntity
     */
    protected function updateRelatedFieldTargetEntity(
        EntityDefinitionConfig $definition,
        EntityDefinitionFieldConfig $field,
        EntityDefinitionConfig $targetEntity
    ) {
        $propertyPath = ConfigUtil::explodePropertyPath($field->getPropertyPath());
        $relatedField = $definition->hasField($propertyPath[0])
            ? $definition->getField($propertyPath[0])
            : null;
        array_shift($propertyPath);

        foreach ($propertyPath as $field) {
            if (!$relatedField->hasTargetEntity()) {
                $relatedField = null;
                break;
            }

            if (!$relatedField->getTargetEntity()->hasField($field)) {
                $relatedField = null;
                break;
            }

            $relatedField = $relatedField->getTargetEntity()->getField($field);
        }

        if ($relatedField) {
            $relatedField->setTargetEntity($targetEntity);
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

    /**
     * @param bool $isCollection
     *
     * @return string
     */
    protected function getAssociationTargetType($isCollection)
    {
        return $isCollection ? 'to-many' : 'to-one';
    }
}
