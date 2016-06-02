<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Validates that requested sorting is supported.
 */
class ValidateSorting implements ProcessorInterface
{
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

        $filterValues = $context->getFilterValues();
        $filters = $context->getFilters();
        foreach ($filters as $filterKey => $filter) {
            if ($filter instanceof SortFilter) {
                $unsupportedFields = $this->validateSortValue(
                    $this->getSortFilterValue($filterValues, $filterKey),
                    $context->getConfigOfSorters()
                );
                if (!empty($unsupportedFields)) {
                    $error = Error::createValidationError(
                        Constraint::SORT,
                        sprintf(
                            'Sorting by "%s" field%s not supported.',
                            implode(', ', $unsupportedFields),
                            count($unsupportedFields) === 1 ? ' is' : 's are'
                        )
                    );
                    $error->setSource(ErrorSource::createByParameter($filterKey));
                    $context->addError($error);
                }
                break;
            }
        }
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
     * @param array|null         $orderBy
     * @param SortersConfig|null $sorters
     *
     * @return string[] The list of fields that cannot be used for sorting
     */
    protected function validateSortValue($orderBy, SortersConfig $sorters = null)
    {
        $unsupportedFields = [];
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                if (null === $sorters
                    || !$sorters->hasField($field)
                    || $sorters->getField($field)->isExcluded()
                ) {
                    $unsupportedFields[] = $field;
                }
            }
        }

        return $unsupportedFields;
    }
}
