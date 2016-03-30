<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Converts values of all requested filters according to the type of a filter.
 */
class NormalizeFilterValues implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

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

        $filterValues = $context->getFilterValues();
        $filters      = $context->getFilters();
        foreach ($filters as $filterKey => $filter) {
            $filterValue = null;
            if ($filterValues->has($filterKey)) {
                $filterValue = $filterValues->get($filterKey);
                if ($filter instanceof StandaloneFilter) {
                    $value = $this->valueNormalizer->normalizeValue(
                        $filterValue->getValue(),
                        $filter->getDataType(),
                        $context->getRequestType(),
                        $filter->isArrayAllowed($filterValue->getOperator())
                    );
                    $filterValue->setValue($value);
                }
            }
        }
    }
}
