<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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

            if ($filter instanceof SortFilter && $context->hasConfigOfSorters()) {
                $this->validateSortValue(
                    null !== $filterValue ? $filterValue->getValue() : null,
                    $context->hasConfigOfSorters() ? $context->getConfigOfSorters() : null
                );
            }

            $filter->apply($criteria, $filterValue);
        }
    }

    /**
     * @param array|null $orderBy
     * @param array|null $sorters
     */
    protected function validateSortValue($orderBy, $sorters)
    {
        if (!empty($orderBy)) {
            $sortFields = !empty($sorters) && !empty($sorters[ConfigUtil::FIELDS])
                ? $sorters[ConfigUtil::FIELDS]
                : [];
            foreach ($orderBy as $field => $direction) {
                if (!array_key_exists($field, $sortFields)) {
                    throw new \RuntimeException(
                        sprintf('Sorting by "%s" is not supported.', $field)
                    );
                }
            }
        }
    }
}
