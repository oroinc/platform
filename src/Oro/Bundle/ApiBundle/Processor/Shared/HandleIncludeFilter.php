<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether the "include" filter exists,
 * and if so, adds the corresponding configuration extra into the context.
 * This filter is used to specify which related entities should be returned.
 */
class HandleIncludeFilter implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;
    private ValueNormalizer $valueNormalizer;

    public function __construct(FilterNamesRegistry $filterNamesRegistry, ValueNormalizer $valueNormalizer)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME)) {
            // the "include" filter is already processed
            return;
        }

        $requestType = $context->getRequestType();
        $filterName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getIncludeFilterName();
        if (!$filterName) {
            // the "include" filter is not supported
            return;
        }

        $filterValue = $context->getFilterValues()->get($filterName);
        if (null === $filterValue) {
            // expanding of related entities was not requested
            return;
        }

        try {
            $includes = $this->valueNormalizer->normalizeValue(
                $filterValue->getValue(),
                DataType::STRING,
                $requestType,
                true
            );
        } catch (\Exception $e) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER)
                    ->setInnerException($e)
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            );

            return;
        }

        if ($context->hasErrors()) {
            // detected errors in the filter value
            return;
        }

        $context->addConfigExtra(new ExpandRelatedEntitiesConfigExtra((array)$includes));
    }
}
