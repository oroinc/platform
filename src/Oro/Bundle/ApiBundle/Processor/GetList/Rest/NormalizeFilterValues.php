<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\RestRequest;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

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
        /** @var GetListContext $context */

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
                        $filter->isArrayAllowed($filterValue->getOperator()) ? RestRequest::ARRAY_DELIMITER : null
                    );
                    $filterValue->setValue($value);
                }
            }
        }
    }
}
