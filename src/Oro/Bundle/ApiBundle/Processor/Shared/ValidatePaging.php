<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that the requested page size is supported.
 */
class ValidatePaging implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;
    private int $maxEntitiesLimit;

    public function __construct(FilterNamesRegistry $filterNamesRegistry, int $maxEntitiesLimit)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->maxEntitiesLimit = $maxEntitiesLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getPageSizeFilterName();
        if (!$context->getFilters()->has($filterName)) {
            // no "page size" filter
            return;
        }

        $filterValue = $context->getFilterValues()->get($filterName);
        if (null === $filterValue) {
            // the paging is not requested
            return;
        }

        $maxResultsLimit = $this->getMaxResultsLimit($context->getConfig());
        if ($maxResultsLimit > 0 && $filterValue->getValue() > $maxResultsLimit) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('The value should be less than or equals to %s.', $maxResultsLimit)
                )->setSource(ErrorSource::createByParameter($filterValue->getSourceKey() ?: $filterName))
            );
        }
    }

    private function getMaxResultsLimit(?EntityDefinitionConfig $config): int
    {
        if (null === $config) {
            return $this->maxEntitiesLimit;
        }

        return $config->getMaxResults() ?? $this->maxEntitiesLimit;
    }
}
