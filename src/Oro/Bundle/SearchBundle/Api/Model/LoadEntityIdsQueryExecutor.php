<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\LoadEntityIdsQueryExecutorInterface;
use Oro\Bundle\ApiBundle\Processor\ContextInterface;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilter;

/**
 * Adds handling of search related exceptions.
 */
class LoadEntityIdsQueryExecutor implements LoadEntityIdsQueryExecutorInterface
{
    public function __construct(
        private readonly LoadEntityIdsQueryExecutorInterface $innerExecutor
    ) {
    }

    #[\Override]
    public function execute(ContextInterface $context, callable $callback): mixed
    {
        try {
            return $this->innerExecutor->execute($context, $callback);
        } catch (InvalidSearchQueryException $e) {
            $context->addError($this->createSearchQueryError(
                $e,
                $context->getFilterValues(),
                $context->getFilters()
            ));

            return null;
        }
    }

    private function createSearchQueryError(
        InvalidSearchQueryException $e,
        FilterValueAccessorInterface $filterValues,
        FilterCollection $filters
    ): Error {
        $error = Error::createValidationError(Constraint::FILTER, $e->getMessage());
        $searchQueryFilterName = $this->getSearchQueryFilterName($filterValues, $filters);
        if ($searchQueryFilterName) {
            $error->setSource(ErrorSource::createByParameter($searchQueryFilterName));
        }

        return $error;
    }

    private function getSearchQueryFilterName(
        FilterValueAccessorInterface $filterValues,
        FilterCollection $filters
    ): ?string {
        foreach ($filterValues->getAll() as $filterKey => $filterValue) {
            if ($filters->get($filterKey) instanceof SearchQueryFilter) {
                return $filterKey;
            }
        }

        return null;
    }
}
