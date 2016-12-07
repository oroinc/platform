<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;

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

        $filters = $context->getFilters();
        $filterValues = $context->getFilterValues();
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $value = $filterValues->get($filterKey);
                try {
                    $filter->apply($criteria, $value);
                } catch (\Exception $e) {
                    $error = null === $value || !$value->getSourceKey()
                        ? Error::createByException($e)
                        : Error::createValidationError(Constraint::FILTER)
                            ->setInnerException($e)
                            ->setSource(ErrorSource::createByParameter($value->getSourceKey()));
                    $context->addError($error);
                }
            }
        }
    }
}
