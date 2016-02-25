<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Excludes fields according to requested fieldset.
 * For example, in JSON.API the "fields[TYPE]" parameter can be used to request only specific fields.
 */
class FilterFieldsByExtra implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassTransformerInterface */
    protected $entityClassTransformer;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param EntityClassTransformerInterface $entityClassTransformer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassTransformerInterface $entityClassTransformer
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->entityClassTransformer = $entityClassTransformer;
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

        $this->filterFields($definition, $entityClass, $context->get(FilterFieldsConfigExtra::NAME));
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param array                  $fieldFilters
     */
    protected function filterFields(EntityDefinitionConfig $definition, $entityClass, array $fieldFilters)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $allowedFields = $this->getAllowedFields($metadata, $fieldFilters);
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
                        $fieldFilters
                    );
                }
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
            $entityType = $this->entityClassTransformer->transform($metadata->name, false);
            if (null !== $entityType && !empty($fieldFilters[$entityType])) {
                $allowedFields = $fieldFilters[$entityType];
            }
        } else {
            $entityClasses = array_unique(array_merge([$metadata->name], $metadata->subClasses));
            foreach ($entityClasses as $entityClass) {
                $entityType = $this->entityClassTransformer->transform($entityClass, false);
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
}
