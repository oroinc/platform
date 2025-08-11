<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateSorting;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SimpleSearchFilter;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that sorting by requested field(s) is supportedwhen data are filtered
 * by "searchText" or "searchQuery" filter or data aggregation is requested.
 */
class ValidateSearchTextSorting implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly SearchMappingProvider $searchMappingProvider,
        private readonly FilterNamesRegistry $filterNamesRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isProcessed(ValidateSorting::OPERATION_NAME)) {
            // the sorting validation was already performed
            return;
        }

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        if (!$context->getConfig()?->isSortingEnabled()) {
            return;
        }

        if (!$this->hasSearchFilter($context->getFilters(), $context->getFilterValues())) {
            return;
        }

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getSortFilterName();
        if (!$context->getFilters()->has($filterName)) {
            // no sort filter
            return;
        }

        $filterValue = $context->getFilterValues()->get($filterName);
        if (null === $filterValue) {
            // sorting is not requested
            return;
        }

        $unsupportedFields = $this->validateSortValues($filterValue, $context);
        if (!empty($unsupportedFields)) {
            $context->addError(
                Error::createValidationError(Constraint::SORT, $this->getValidationErrorMessage($unsupportedFields))
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            );
        }
        $context->setProcessed(ValidateSorting::OPERATION_NAME);
    }

    private function hasSearchFilter(FilterCollection $filters, FilterValueAccessorInterface $filterValues): bool
    {
        foreach ($filterValues->getAll() as $filterKey => $filterValue) {
            if ($this->isSearchFilter($filters->get($filterKey))) {
                return true;
            }
        }

        return false;
    }

    private function isSearchFilter(?FilterInterface $filter): bool
    {
        return
            $filter instanceof SimpleSearchFilter
            || $filter instanceof SearchQueryFilter
            || $filter instanceof SearchAggregationFilter;
    }

    private function getValidationErrorMessage(array $unsupportedFields): string
    {
        return \sprintf(
            'Sorting by "%s" field%s not supported.',
            implode(', ', $unsupportedFields),
            \count($unsupportedFields) === 1 ? ' is' : 's are'
        );
    }

    private function validateSortValues(FilterValue $filterValue, Context $context): array
    {
        $orderBy = $filterValue->getValue();
        if (empty($orderBy)) {
            return [];
        }

        $unsupportedFields = [];
        $sortableFieldTypes = $this->searchMappingProvider->getSearchFieldTypes(
            $context->getManageableEntityClass($this->doctrineHelper)
        );
        foreach ($orderBy as $fieldName => $direction) {
            if (!isset($sortableFieldTypes[$fieldName])) {
                $unsupportedFields[] = $fieldName;
            }
        }

        return $unsupportedFields;
    }
}
