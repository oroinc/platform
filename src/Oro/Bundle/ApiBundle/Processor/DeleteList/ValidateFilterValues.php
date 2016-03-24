<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Checks if filter values was set.
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
        $filters      = $context->getFilters();
        $valuesExist = false;
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $valuesExist = true;
                break;
            }
        }

        if (!$valuesExist) {
            throw new BadRequestHttpException('Filters does not set.');
        }
    }
}
