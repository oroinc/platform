<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Validates that requested sorting is supported.
 */
class ValidateSorting implements ProcessorInterface
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
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $sortFilterKey = $this->getSortFilterKey($context->getFilters());
        if (!$sortFilterKey) {
            return;
        }

        $sortFilterValue = $context->getFilterValues()->get($sortFilterKey);
        if (null === $sortFilterValue) {
            return;
        }

        $unsupportedFields = $this->validateSortValues($sortFilterValue, $context);
        if (!empty($unsupportedFields)) {
            $error = Error::createValidationError(
                Constraint::SORT,
                sprintf(
                    'Sorting by "%s" field%s not supported.',
                    implode(', ', $unsupportedFields),
                    count($unsupportedFields) === 1 ? ' is' : 's are'
                )
            );
            $error->setSource(ErrorSource::createByParameter($sortFilterKey));
            $context->addError($error);
        }
    }

    /**
     * @param FilterCollection $filters
     *
     * @return string|null
     */
    protected function getSortFilterKey(FilterCollection $filters)
    {
        foreach ($filters as $filterKey => $filter) {
            if ($filter instanceof SortFilter) {
                return $filterKey;
            }
        }

        return null;
    }

    /**
     * @param FilterValue $filterValue
     * @param Context     $context
     *
     * @return string[] The list of fields that cannot be used for sorting
     */
    protected function validateSortValues(FilterValue $filterValue, Context $context)
    {
        $orderBy = $filterValue->getValue();
        if (empty($orderBy)) {
            return [];
        }

        $sorters = $context->getConfigOfSorters();

        $unsupportedFields = [];
        foreach ($orderBy as $fieldName => $direction) {
            $path = explode('.', $fieldName);
            $propertyPath = count($path) > 1
                ? $this->validateAssociationSorter($path, $context)
                : $this->validateSorter($fieldName, $sorters);
            if (!$propertyPath) {
                $unsupportedFields[] = $fieldName;
            } elseif ($propertyPath !== $fieldName) {
                $this->renameSortField($filterValue, $fieldName, $propertyPath);
            }
        }

        return $unsupportedFields;
    }

    /**
     * @param FilterValue $filterValue
     * @param string      $oldFieldName
     * @param string      $newFieldName
     */
    protected function renameSortField(FilterValue $filterValue, $oldFieldName, $newFieldName)
    {
        $orderBy = $filterValue->getValue();
        $updatedOrderBy = [];
        foreach ($orderBy as $fieldName => $direction) {
            if ($fieldName === $oldFieldName) {
                $fieldName = $newFieldName;
            }
            $updatedOrderBy[$fieldName] = $direction;
        }
        $filterValue->setValue($updatedOrderBy);
    }

    /**
     * @param string             $fieldName
     * @param SortersConfig|null $sorters
     *
     * @return string|null The real field name if the sorting is allowed; otherwise, NULL
     */
    protected function validateSorter($fieldName, SortersConfig $sorters = null)
    {
        if (null === $sorters) {
            return null;
        }

        $sorter = $sorters->getField($fieldName);
        if (null === $sorter || $sorter->isExcluded()) {
            return null;
        }

        return $sorter->getPropertyPath() ?: $fieldName;
    }

    /**
     * @param string[] $path
     * @param Context  $context
     *
     * @return string|null The real association path if the sorting is allowed; otherwise, NULL
     */
    protected function validateAssociationSorter(array $path, Context $context)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        if (!$metadata) {
            return null;
        }

        $targetFieldName = array_pop($path);
        list($targetSorters, $associations) = $this->getAssociationSorters($path, $context, $metadata);
        $targetFieldName = $this->validateSorter($targetFieldName, $targetSorters);
        if (!$targetFieldName) {
            return null;
        }

        return $associations . '.' . $targetFieldName;
    }

    /**
     * @param string[]      $path
     * @param Context       $context
     * @param ClassMetadata $metadata
     *
     * @return array [sorters config, associations]
     */
    protected function getAssociationSorters(array $path, Context $context, ClassMetadata $metadata)
    {
        $targetConfigExtras = [
            new EntityDefinitionConfigExtra($context->getAction()),
            new SortersConfigExtra()
        ];

        $config = $context->getConfig();
        $sorters = null;
        $associations = [];

        foreach ($path as $fieldName) {
            if (!$config->hasField($fieldName)) {
                return [null, null];
            }

            $associationName = $config->getField($fieldName)->getPropertyPath() ?: $fieldName;
            if (!$metadata->hasAssociation($associationName)) {
                return [null, null];
            }

            $targetClass = $metadata->getAssociationTargetClass($associationName);
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass, false);
            if (!$metadata) {
                return [null, null];
            }

            $targetConfig = $this->configProvider->getConfig(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $targetConfigExtras
            );
            if (!$targetConfig->hasDefinition()) {
                return [null, null];
            }

            $config = $targetConfig->getDefinition();
            $sorters = $targetConfig->getSorters();
            $associations[] = $associationName;
        }

        return [$sorters, implode('.', $associations)];
    }
}
