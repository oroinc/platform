<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts values of all requested filters according to the type of a filter.
 * Validated that all requested filters are supported.
 */
class NormalizeFilterValues implements ProcessorInterface
{
    /** @var ValueNormalizer */
    private $valueNormalizer;

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
                    $operator = $filterValue->getOperator();
                    $dataType = $filter->getDataType();
                    $isArrayAllowed = $filter->isArrayAllowed($operator);
                    $isRangeAllowed = $filter->isRangeAllowed($operator);
                    if (ComparisonFilter::EXISTS === $operator) {
                        $dataType = DataType::BOOLEAN;
                        $isArrayAllowed = false;
                        $isRangeAllowed = false;
                    }
                    try {
                        $value = $this->valueNormalizer->normalizeValue(
                            $filterValue->getValue(),
                            $dataType,
                            $context->getRequestType(),
                            $isArrayAllowed,
                            $isRangeAllowed
                        );
                        $filterValue->setValue($value);
                    } catch (\Exception $e) {
                        $context->addError(
                            $this->createFilterError($filterKey, $filterValue)->setInnerException($e)
                        );
                    }
                }
            } else {
                $context->addError(
                    $this->createFilterError($filterKey, $filterValue)->setDetail('The filter is not supported.')
                );
            }
        }
    }

    /**
     * @param string      $filterKey
     * @param FilterValue $filterValue
     *
     * @return Error
     */
    private function createFilterError(string $filterKey, FilterValue $filterValue): Error
    {
        return Error::createValidationError(Constraint::FILTER)
            ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey() ?: $filterKey));
    }
}
