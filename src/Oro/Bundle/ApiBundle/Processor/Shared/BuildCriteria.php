<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

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

        /** @var FilterInterface[] $filters */
        $filters = $context->getFilters();
        $filterValues = $context->getFilterValues();

        /**
         * it is important to iterate by $filters, not by $filterValues,
         * because the order of filters is matter,
         * e.g. "page size" filter should be processed before "page number" filter
         * @see \Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultPaging::addPageNumberFilter
         */
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $filterValue = $filterValues->get($filterKey);
                try {
                    $filter->apply($criteria, $filterValue);
                } catch (\Exception $e) {
                    $error = null === $filterValue || !$filterValue->getSourceKey()
                        ? Error::createByException($e)
                        : Error::createValidationError(Constraint::FILTER)
                            ->setInnerException($e)
                            ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()));
                    $context->addError($error);
                }
            } elseif ($filter instanceof StandaloneFilterWithDefaultValue) {
                $filter->apply($criteria);
            }
        }
    }
}
