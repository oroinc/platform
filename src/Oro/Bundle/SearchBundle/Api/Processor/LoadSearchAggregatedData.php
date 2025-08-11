<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Model\LoadEntityIdsBySearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads search aggregated data using a search query stored in the context.
 */
class LoadSearchAggregatedData implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        /** @var LoadEntityIdsBySearchQuery|null $searchQuery */
        $searchQuery = $context->get(BuildSearchQuery::SEARCH_QUERY);
        if (null === $searchQuery) {
            return;
        }

        try {
            $aggregatedData = $searchQuery->getAggregatedData();
        } catch (InvalidSearchQueryException $e) {
            $context->addError($this->createAggregatedDataError($e, $context));

            return;
        }
        if ($aggregatedData) {
            $context->addInfoRecord('aggregatedData', $aggregatedData);
        }
    }

    private function createAggregatedDataError(InvalidSearchQueryException $e, ListContext $context): Error
    {
        $error = Error::createValidationError(Constraint::FILTER, $e->getMessage());
        $aggregationFilterName = $this->getSearchAggregationFilterName($context);
        if ($aggregationFilterName) {
            $error->setSource(ErrorSource::createByParameter($aggregationFilterName));
        }

        return $error;
    }

    private function getSearchAggregationFilterName(ListContext $context): ?string
    {
        $filterValues = $context->getFilterValues()->getAll();
        foreach ($filterValues as $filterKey => $filterValue) {
            if ($context->getFilters()->get($filterKey) instanceof SearchAggregationFilter) {
                return $filterKey;
            }
        }

        return null;
    }
}
