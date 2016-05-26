<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
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
                $filterValues->remove($filterKey);
            }
        }

        // Process unknown filters
        foreach ($filterValues->getAll() as $filterKey => $filterValue) {
            /** @var FilterValue $filterValue */
            if ($filters->has($filterKey)) {
                continue;
            }

            $filterValueDataType = $this->getFilterValueDataType($context, $filterKey, $filterValue);
            if (!$filterValueDataType) {
                continue;
            }

            /** @var ComparisonFilter $filter */
            $filter = new ComparisonFilter($filterValueDataType);
            $filter->setField($filterValue->getPath());
            $filter->apply($criteria, $filterValue);

            $filters->add($filterKey, $filter);
        }
    }

    /**
     * @param Context     $context
     * @param string      $filterKey
     * @param FilterValue $filterValue
     *
     * @return string|null
     */
    protected function getFilterValueDataType($context, $filterKey, $filterValue)
    {
        $metadata = $context->getMetadata();

        $filterParts = explode('.', $filterValue->getPath());
        $filterPartsCount = count($filterParts);

        foreach ($filterParts as $index => $fieldName) {
            //the last part reached
            if ($index === $filterPartsCount - 1) {
                break;
            }

            //all parts, except last one should be associations
            if (!$metadata->hasAssociation($fieldName)) {
                $context->addError(
                    Error::createValidationError(
                        Constraint::FILTER,
                        sprintf('All resource parts (except last), should be an association, see the "%s"', $fieldName)
                    )->setSource(ErrorSource::createByParameter($filterKey))
                );
                break;
            }

            $metadata = $context->getMetadata($metadata->getAssociation($fieldName)->getTargetClassName());
        }

        $fieldName = end($filterParts);
        if (!$metadata->hasAssociation($fieldName) && !$metadata->hasField($fieldName)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Unknown resource "%s"', $fieldName)
                )->setSource(ErrorSource::createByParameter($filterKey))
            );

            return null;
        }

        return $metadata->hasAssociation($fieldName)
            ? $metadata->getAssociation($fieldName)->getDataType()
            : $metadata->getField($fieldName)->getDataType();
    }
}
