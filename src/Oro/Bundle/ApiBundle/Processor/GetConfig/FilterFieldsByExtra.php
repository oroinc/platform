<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes fields according to requested fieldset.
 * For example, in JSON:API the "fields[TYPE]" filter can be used to request only specific fields.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FilterFieldsByExtra implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $normalizedFieldFilters = $context->get(FilterFieldsConfigExtra::NAME);
        if (null === $normalizedFieldFilters) {
            $normalizedFieldFilters = [];
        } else {
            $normalizedFieldFilters = $this->normalizeFieldFilters(
                $normalizedFieldFilters,
                $context->getRequestType()
            );
        }

        $entityClass = $context->getClassName();
        if (!$definition->isFieldsetEnabled()) {
            if (is_a($entityClass, EntityIdentifier::class, true)) {
                return;
            }

            if (!$this->isSupported($context, $normalizedFieldFilters)) {
                throw new NotSupportedConfigOperationException($entityClass, FilterFieldsConfigExtra::NAME);
            }
        }

        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $this->filterEntityFields($definition, $entityClass, $normalizedFieldFilters);
        } else {
            $this->filterObjectFields($definition, $entityClass, $normalizedFieldFilters);
        }
    }

    private function isSupported(ConfigContext $context, array $normalizedFieldFilters): bool
    {
        $result = false;
        if (!empty($normalizedFieldFilters) && $context->getParentClassName() && $context->getAssociationName()) {
            $parentClass = $context->getParentClassName();
            if (isset($normalizedFieldFilters[$parentClass])
                && \count($normalizedFieldFilters) === 1
                && \count($normalizedFieldFilters[$parentClass]) === 1
                && $normalizedFieldFilters[$parentClass][0] === $context->getAssociationName()
            ) {
                $result = true;
            }
        }

        return $result;
    }

    private function normalizeFieldFilters(array $fieldFilters, RequestType $requestType): array
    {
        $result = [];
        foreach ($fieldFilters as $entity => $fields) {
            if (!str_contains($entity, '\\')) {
                $entityClass = ValueNormalizerUtil::tryConvertToEntityClass(
                    $this->valueNormalizer,
                    $entity,
                    $requestType
                );
                if ($entityClass) {
                    $entity = $entityClass;
                }
            }
            if (isset($result[$entity])) {
                $fields = array_unique(array_merge($result[$entity], $fields ?? []));
            }
            $result[$entity] = $fields;
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function filterEntityFields(
        EntityDefinitionConfig $definition,
        string $entityClass,
        array $fieldFilters
    ): void {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $allowedFields = $this->getAllowedFields($metadata, $fieldFilters);
        if (null !== $allowedFields) {
            $idFieldNames = $this->getEntityIdentifierFieldNames($metadata, $definition);
            $fields = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                if (!$field->isExcluded()
                    && !(
                        $field->isMetaProperty()
                        && (
                            ConfigUtil::isRequiredMetaProperty($fieldName)
                            || ConfigUtil::isRequiredMetaProperty($field->getPropertyPath($fieldName))
                        )
                    )
                    && !\in_array($fieldName, $allowedFields, true)
                    && !\in_array($fieldName, $idFieldNames, true)
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

    private function getEntityIdentifierFieldNames(ClassMetadata $metadata, EntityDefinitionConfig $definition): array
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function filterObjectFields(
        EntityDefinitionConfig $definition,
        string $entityClass,
        array $fieldFilters
    ): void {
        if (!\array_key_exists($entityClass, $fieldFilters)) {
            return;
        }

        $allowedFields = $fieldFilters[$entityClass];
        if (null !== $allowedFields) {
            $idFieldNames = $definition->getIdentifierFieldNames();
            $fields = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                if (!$field->isExcluded()
                    && !\in_array($fieldName, $allowedFields, true)
                    && !\in_array($fieldName, $idFieldNames, true)
                ) {
                    $field->setExcluded();
                }
            }
        }

        $fields = $definition->getFields();
        foreach ($fields as $field) {
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
    private function getAllowedFields(ClassMetadata $metadata, array $fieldFilters): ?array
    {
        $allowedFields = null;
        if ($metadata->isInheritanceTypeNone()) {
            if (isset($fieldFilters[$metadata->name])) {
                $allowedFields = $fieldFilters[$metadata->name];
            }
        } else {
            $entityClasses = array_unique(array_merge([$metadata->name], $metadata->subClasses));
            foreach ($entityClasses as $entityClass) {
                if (isset($fieldFilters[$entityClass])) {
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
