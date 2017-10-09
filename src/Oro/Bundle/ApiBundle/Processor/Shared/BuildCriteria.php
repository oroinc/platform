<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
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
        $filterValues = $context->getFilterValues()->getAll();
        foreach ($filterValues as $filterKey => $filterValue) {
            if ($filters->has($filterKey)) {
                $filter = $filters->get($filterKey);
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
            }
        }
    }
}
