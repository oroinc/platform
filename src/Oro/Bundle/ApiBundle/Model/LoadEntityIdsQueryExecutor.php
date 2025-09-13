<?php

namespace Oro\Bundle\ApiBundle\Model;

use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ContextInterface;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * This service is used to wrap execution of queries that load identifiers of entities
 * to catch possible exceptions and add them as validation errors in the context.
 */
class LoadEntityIdsQueryExecutor implements LoadEntityIdsQueryExecutorInterface
{
    public function __construct(
        private readonly FilterNamesRegistry $filterNamesRegistry
    ) {
    }

    #[\Override]
    public function execute(ContextInterface $context, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (InvalidSorterException $e) {
            $context->addError($this->createSortingError(
                $e,
                $context->getFilterValues(),
                $context->getRequestType()
            ));

            return null;
        }
    }

    private function createSortingError(
        InvalidSorterException $e,
        FilterValueAccessorInterface $filterValues,
        RequestType $requestType
    ): Error {
        $error = Error::createValidationError(Constraint::SORT, $e->getMessage());
        $sortFilterName = $this->getSortFilterName($filterValues, $requestType);
        if ($sortFilterName) {
            $error->setSource(ErrorSource::createByParameter($sortFilterName));
        }

        return $error;
    }

    private function getSortFilterName(
        FilterValueAccessorInterface $filterValues,
        RequestType $requestType
    ): ?string {
        $sortFilterName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getSortFilterName();
        if (!$sortFilterName) {
            return null;
        }

        return $filterValues->get($sortFilterName)?->getSourceKey();
    }
}
