<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Checks whether at least one filter is provided.
 */
class ValidateFilterValues implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $filterValues = $context->getFilterValues();
        $filters = $context->getFilters();
        $hasFilters = false;
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $hasFilters = true;
                break;
            }
        }

        if (!$hasFilters) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    'At least one filter must be provided.'
                )
            );
        }
    }
}
