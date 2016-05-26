<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Applies all requested filters to the Criteria object.
 */
class BuildCriteria implements ProcessorInterface
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

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $filterValues = $context->getFilterValues();
        $filters = $context->getFilters();
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $filter->apply($criteria, $filterValues->get($filterKey));
            }
        }

        $subresourceFilters = $this->getSubresourceFilterValues($filterValues);
        foreach ($subresourceFilters as $filterKey => $filterValue) {
            /** @var FilterValue $filterValue */
            if ($filters->has($filterKey)) {
                continue;
            }

            $filterValueDataType = $this->validateFilterValueAndGetType($context, $filterValue);

            /** @var ComparisonFilter $filter */
            $filter = new ComparisonFilter($filterValueDataType);
            $filter->setField($filterValue->getPath());
            $filter->apply($criteria, $filterValue);
            $filters->add($filterKey, $filter);
        }
    }

    /**
     * @param FilterValueAccessorInterface $filterValues
     *
     * @return array
     */
    protected function getSubresourceFilterValues($filterValues)
    {
        //Process filters by sub resource
        $filterValuesKeys = array_filter(
            array_keys($filterValues->getAll()),
            function ($filterKey) {
                return strpos($filterKey, 'filter') === 0;
            }
        );

        return array_intersect_key(
            $filterValues->getAll(),
            array_flip($filterValuesKeys)
        );
    }

    /**
     * @param Context     $context
     * @param FilterValue $filterValue
     *
     * @return string
     */
    protected function validateFilterValueAndGetType($context, $filterValue)
    {
        $metadata = $context->getMetadata();
        $filterParts = explode('.', $filterValue->getPath());
        $filterPartsCount = count($filterParts);

        foreach ($filterParts as $index => $fieldName) {
            //all parts, except last one should be associations
            if ($index !== $filterPartsCount - 1
                && !$this->isAssociation($metadata, $fieldName)
            ) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'All parts of filter, except last, should be an association, but the "%s" is NOT.',
                        $fieldName
                    )
                );
            }

            //the last part reached, means validation passed
            if ($index === $filterPartsCount - 1) {
                return $this->isAssociation($metadata, $fieldName)
                    ? $metadata->getAssociation($fieldName)->getDataType()
                    : $metadata->getField($fieldName)->getDataType();
            } else {
                $metadata = $context->getMetadata($metadata->getAssociation($fieldName)->getTargetClassName());
            }
        }
    }

    /**
     * @param EntityMetadata|null $metadata
     * @param string              $fieldName
     *
     * @return bool
     */
    protected function isAssociation($metadata, $fieldName)
    {
        return $metadata->hasAssociation($fieldName);
    }
}
