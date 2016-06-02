<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
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

        $sortFilterValue = $this->getSortFilterValue($context->getFilterValues(), $sortFilterKey);
        if (empty($sortFilterValue)) {
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
     * @param FilterValueAccessorInterface $filterValues
     * @param string                       $filterKey
     *
     * @return array|null
     */
    protected function getSortFilterValue(FilterValueAccessorInterface $filterValues, $filterKey)
    {
        $filterValue = null;
        if ($filterValues->has($filterKey)) {
            $filterValue = $filterValues->get($filterKey);
            if (null !== $filterValue) {
                $filterValue = $filterValue->getValue();
                if (empty($filterValue)) {
                    $filterValue = null;
                }
            }
        }

        return $filterValue;
    }

    /**
     * @param array   $orderBy
     * @param Context $context
     *
     * @return string[] The list of fields that cannot be used for sorting
     */
    protected function validateSortValues($orderBy, Context $context)
    {
        $sorters = $context->getConfigOfSorters();

        $unsupportedFields = [];
        foreach ($orderBy as $fieldName => $direction) {
            $path = explode('.', $fieldName);
            if (count($path) > 1) {
                if (!$this->validateAssociationSorter($path, $context)) {
                    $unsupportedFields[] = $fieldName;
                }
            } elseif (!$this->validateSorter($fieldName, $sorters)) {
                $unsupportedFields[] = $fieldName;
            }
        }

        return $unsupportedFields;
    }

    /**
     * @param string             $fieldName
     * @param SortersConfig|null $sorters
     *
     * @return bool
     */
    protected function validateSorter($fieldName, SortersConfig $sorters = null)
    {
        return
            null !== $sorters
            && $sorters->hasField($fieldName)
            && !$sorters->getField($fieldName)->isExcluded();
    }

    /**
     * @param string[] $path
     * @param Context  $context
     *
     * @return bool
     */
    protected function validateAssociationSorter(array $path, Context $context)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        if (!$metadata) {
            return false;
        }
        $targetFieldName = array_pop($path);
        $targetSorters = $this->getAssociationSorters($path, $context, $metadata);
        if (!$this->validateSorter($targetFieldName, $targetSorters)) {
            return false;
        }

        return true;
    }

    /**
     * @param string[]      $path
     * @param Context       $context
     * @param ClassMetadata $metadata
     *
     * @return SortersConfig|null
     */
    protected function getAssociationSorters(array $path, Context $context, ClassMetadata $metadata)
    {
        $targetConfigExtras = [
            new EntityDefinitionConfigExtra($context->getAction()),
            new SortersConfigExtra()
        ];

        $sorters = null;

        $config = $context->getConfig();

        foreach ($path as $fieldName) {
            if (!$config->hasField($fieldName)) {
                return null;
            }

            $associationName = $config->getField($fieldName)->getPropertyPath() ?: $fieldName;
            if (!$metadata->hasAssociation($associationName)) {
                return null;
            }

            $targetClass = $metadata->getAssociationTargetClass($associationName);
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass, false);
            if (!$metadata) {
                return null;
            }

            $targetConfig = $this->configProvider->getConfig(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $targetConfigExtras
            );
            if (!$targetConfig->hasDefinition()) {
                return null;
            }

            $config = $targetConfig->getDefinition();
            $sorters = $targetConfig->getSorters();
        }

        return $sorters;
    }
}
