<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
        $this->doctrineHelper  = $doctrineHelper;
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
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->filterFields(
            $definition,
            $entityClass,
            $context->get(FilterFieldsConfigExtra::NAME),
            $context->getRequestType()
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param array                  $fieldFilters
     * @param RequestType            $requestType
     */
    protected function filterFields(
        EntityDefinitionConfig $definition,
        $entityClass,
        array $fieldFilters,
        RequestType $requestType
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $allowedFields = $this->getAllowedFields($metadata, $fieldFilters, $requestType);
        if (null !== $allowedFields) {
            $idFieldNames = $metadata->getIdentifierFieldNames();
            $fields       = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                if (!$field->isExcluded()
                    && !in_array($fieldName, $allowedFields, true)
                    && !in_array($fieldName, $idFieldNames, true)
                    && !ConfigUtil::isMetadataProperty($field->getPropertyPath() ?: $fieldName)
                ) {
                    $field->setExcluded();
                }
            }
        }

        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->hasTargetEntity()) {
                $propertyPath = $field->getPropertyPath() ?: $fieldName;
                if ($metadata->hasAssociation($propertyPath)) {
                    $this->filterFields(
                        $field->getTargetEntity(),
                        $metadata->getAssociationTargetClass($propertyPath),
                        $fieldFilters,
                        $requestType
                    );
                }
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param array         $fieldFilters
     * @param RequestType   $requestType
     *
     * @return string[]|null
     */
    protected function getAllowedFields(ClassMetadata $metadata, array $fieldFilters, RequestType $requestType)
    {
        $allowedFields = null;
        if ($metadata->inheritanceType === ClassMetadata::INHERITANCE_TYPE_NONE) {
            $entityType = $this->convertToEntityType($metadata->name, $requestType);
            if (null !== $entityType && !empty($fieldFilters[$entityType])) {
                $allowedFields = $fieldFilters[$entityType];
            }
        } else {
            $entityClasses = array_unique(array_merge([$metadata->name], $metadata->subClasses));
            foreach ($entityClasses as $entityClass) {
                $entityType = $this->convertToEntityType($entityClass, $requestType);
                if (null !== $entityType && !empty($fieldFilters[$entityType])) {
                    if (null === $allowedFields) {
                        $allowedFields = $fieldFilters[$entityType];
                    } else {
                        $allowedFields = array_unique(array_merge($allowedFields, $fieldFilters[$entityType]));
                    }
                }
            }
        }

        return $allowedFields;
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function convertToEntityType($entityClass, RequestType $requestType)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }
}
