<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

/**
 * The responsibility of this processor is finding "fields[]" filter parameters and setting them into context.
 */
class NormalizeFieldsParameter implements ProcessorInterface
{
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filterValues = $context->getFilterValues();

        if ($filterValues instanceof RestFilterValueAccessor) {
            $filters = $filterValues->getAll();
            if (null !== $filters) {
                $fieldsFilters = [];
                foreach ($filters as $filterKey => $filterValue) {
                    if (preg_match('/^fields\[(.*)\]$/', $filterKey, $relationName)) {
                        $fieldsFilters[$relationName[1]] = explode(',', $filterValue->getValue());
                    }
                }

                if ($fieldsFilters) {
                    $context->addConfigExtra(new FilterFieldsConfigExtra($fieldsFilters));
                }
            }
        }
    }
}
