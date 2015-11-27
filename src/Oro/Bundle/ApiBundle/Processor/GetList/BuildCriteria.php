<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCriteria implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria     = $context->getCriteria();
        $filterValues = $context->getFilterValues();
        $filters      = $context->getFilters();
        foreach ($filters as $filterKey => $filter) {
            $filterValue = null;
            if ($filterValues->has($filterKey)) {
                $filterValue = $filterValues->get($filterKey);
            }

            if ($filter instanceof SortFilter) {
                $sortersConfigFields = $context->getConfigOfSorters()[ConfigUtil::FIELDS];
                foreach ($filterValue->getValue() as $field => $direction) {
                    if (!array_key_exists($field, $sortersConfigFields)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Sorting by the field "%s" does not supports',
                                $field
                            )
                        );
                    }
                }
            }
            $filter->apply($criteria, $filterValue);
        }
    }
}
