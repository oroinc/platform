<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Excludes fields according to requested fieldset.
 * For example, in JSON.API the "fields[TYPE]" parameter can be used to request only specific fields.
 */
class FilterFieldsByExtra implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
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
        if (!$definition->isFieldsetEnabled()) {
            if (is_a($entityClass, EntityIdentifier::class, true)) {
                return;
            }

            throw new NotSupportedConfigOperationException($entityClass, FilterFieldsConfigExtra::NAME);
        }

        $normalizedFieldFilters = $this->normalizeFieldFilters(
            $context->get(FilterFieldsConfigExtra::NAME),
            $context->getRequestType()
        );

        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $this->filterEntityFields($definition, $entityClass, $normalizedFieldFilters);
        } else {
            $this->filterObjectFields($definition, $entityClass, $normalizedFieldFilters);
        }
    }

    /**
     * @param array       $fieldFilters
     * @param RequestType $requestType
     *
     * @return array
     */
    protected function normalizeFieldFilters(array $fieldFilters, RequestType $requestType)
    {
        $result = [];
        foreach ($fieldFilters as $entity => $fields) {
            if (false === strpos($entity, '\\')) {
                $entityClass = ValueNormalizerUtil::convertToEntityClass(
                    $this->valueNormalizer,
                    $entity,
                    $requestType,
                    false
                );
                if ($entityClass) {
                    $entity = $entityClass;
                }
            }
            $result[$entity] = $fields;
        }

        return $result;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param array                  $fieldFilters
     */
    protected function filterEntityFields(
        EntityDefinitionConfig $definition,
        $entityClass,
        array $fieldFilters
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $allowedFields = $this->getAllowedFields($metadata, $fieldFilters);
        if (null !== $allowedFields) {
            $idFieldNames = $this->getEntityIdentifierFieldNames($metadata, $definition);
            $fields = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                if (!$field->isExcluded()
                    && !$field->isMetaProperty()
                    && !in_array($fieldName, $allowedFields, true)
                    && !in_array($fieldName, $idFieldNames, true)
                ) {
                    $field->setExcluded();
                }
            }
        }

        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->hasTargetEntity()) {
                $propertyPath = $field->getPropertyPath($fieldName);
                if ($metadata->hasAssociation($propertyPath)) {
                    $this->filterEntityFields(
                        $field->getTargetEntity(),
                        $metadata->getAssociationTargetClass($propertyPath),
                        $fieldFilters
                    );
                }
            }
        }
    }

    /**
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     *
     * @return array
     */
    protected function getEntityIdentifierFieldNames(ClassMetadata $metadata, EntityDefinitionConfig $definition)
    {
        $idFieldNames = $definition->getIdentifierFieldNames();
        if (empty($idFieldNames)) {
            $idFieldNames = [];
            $metadataIdFieldNames = $metadata->getIdentifierFieldNames();
            foreach ($metadataIdFieldNames as $propertyPath) {
                $idFieldNames[] = $definition->findFieldNameByPropertyPath($propertyPath) ?: $propertyPath;
            }
        }

        return $idFieldNames;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param array                  $fieldFilters
     */
    protected function filterObjectFields(
        EntityDefinitionConfig $definition,
        $entityClass,
        array $fieldFilters
    ) {
        if (empty($fieldFilters[$entityClass])) {
            return;
        }

        $allowedFields = $fieldFilters[$entityClass];
        if (null !== $allowedFields) {
            $idFieldNames = $definition->getIdentifierFieldNames();
            $fields = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                if (!$field->isExcluded()
                    && !in_array($fieldName, $allowedFields, true)
                    && !in_array($fieldName, $idFieldNames, true)
                ) {
                    $field->setExcluded();
                }
            }
        }

        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $targetClass = $field->getTargetClass();
            if ($targetClass && $field->hasTargetEntity()) {
                $this->filterObjectFields($field->getTargetEntity(), $targetClass, $fieldFilters);
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param array         $fieldFilters
     *
     * @return string[]|null
     */
    protected function getAllowedFields(ClassMetadata $metadata, array $fieldFilters)
    {
        $allowedFields = null;
        if ($metadata->inheritanceType === ClassMetadata::INHERITANCE_TYPE_NONE) {
            if (!empty($fieldFilters[$metadata->name])) {
                $allowedFields = $fieldFilters[$metadata->name];
            }
        } else {
            $entityClasses = array_unique(array_merge([$metadata->name], $metadata->subClasses));
            foreach ($entityClasses as $entityClass) {
                if (!empty($fieldFilters[$entityClass])) {
                    if (null === $allowedFields) {
                        $allowedFields = $fieldFilters[$entityClass];
                    } else {
                        $allowedFields = array_unique(array_merge($allowedFields, $fieldFilters[$entityClass]));
                    }
                }
            }
        }

        return $allowedFields;
    }
}
