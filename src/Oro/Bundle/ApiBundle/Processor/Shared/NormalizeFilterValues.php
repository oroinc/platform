<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

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

        $filters = $context->getFilters();
        $filterValues = $context->getFilterValues()->getAll();
        foreach ($filterValues as $filterKey => $filterValue) {
            if ($filters->has($filterKey)) {
                $filter = $filters->get($filterKey);
                if ($filter instanceof StandaloneFilter) {
                    try {
                        $value = $this->valueNormalizer->normalizeValue(
                            $filterValue->getValue(),
                            $filter->getDataType(),
                            $context->getRequestType(),
                            $filter->isArrayAllowed($filterValue->getOperator()),
                            $filter->isRangeAllowed($filterValue->getOperator())
                        );
                        $filterValue->setValue($value);
                    } catch (\Exception $e) {
                        $error = Error::createByException($e)
                            ->setStatusCode(Response::HTTP_BAD_REQUEST)
                            ->setSource(ErrorSource::createByParameter($filterKey));
                        $context->addError($error);
                    }
                }
            }
        }
    }
}
