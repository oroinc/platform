<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether at least one filter is provided.
 */
class ValidateFilterValues implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var DeleteListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $filterValueAccessor = $context->getFilterValues();
        $filterCollection = $context->getFilters();
        $hasFilters = false;
        foreach ($filterCollection as $filterKey => $filter) {
            if ($filterValueAccessor->has($filterKey)) {
                $hasFilters = true;
                break;
            }
        }

        if (!$hasFilters) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'At least one filter must be provided.')
            );
        }
    }
}
