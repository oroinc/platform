<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SimpleSearchFilter;
use Oro\Bundle\SearchBundle\Api\Model\LoadEntityIdsBySearchQuery;
use Oro\Bundle\SearchBundle\Api\Model\SearchQueryExecutorInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Builds a search query object that will be used to get entities via the search index.
 */
class BuildSearchQuery implements ProcessorInterface
{
    public const SEARCH_QUERY = 'search_query';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly SearchIndexer $searchIndexer,
        private readonly SearchQueryExecutorInterface $searchQueryExecutor,
        private readonly AbstractSearchMappingProvider $searchMappingProvider,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            return;
        }

        if (!$this->hasSearchFilter($context->getFilters(), $context->getFilterValues())) {
            return;
        }

        if ($this->hasNonSearchFilter($context->getFilters(), $context->getFilterValues())) {
            $context->addError($this->createSearchFilterError(
                'The search filter cannot be used together with other filters.',
                $context
            ));

            return;
        }

        if (!$this->authorizationChecker->isGranted('oro_search')) {
            $context->addError($this->createSearchFilterError(
                'This filter cannot be used because the search capability is disabled.',
                $context
            ));

            return;
        }

        $searchQuery = new LoadEntityIdsBySearchQuery(
            $this->searchIndexer,
            $this->searchQueryExecutor,
            $this->searchMappingProvider->getEntityAlias(
                $context->getManageableEntityClass($this->doctrineHelper)
            ),
            $criteria->getWhereExpression(),
            $criteria->getFirstResult(),
            $criteria->getMaxResults(),
            $criteria->getOrderings(),
            (bool)$context->getConfig()?->getHasMore()
        );
        $context->set(self::SEARCH_QUERY, $searchQuery);
        $context->setQuery($searchQuery);
        $context->setCriteria($this->buildCriteria($criteria));
    }

    private function hasSearchFilter(FilterCollection $filters, FilterValueAccessorInterface $filterValues): bool
    {
        foreach ($filterValues->getAll() as $filterKey => $values) {
            if ($this->isSearchFilter($filters->get($filterKey))) {
                return true;
            }
        }

        return false;
    }

    private function hasNonSearchFilter(FilterCollection $filters, FilterValueAccessorInterface $filterValues): bool
    {
        foreach ($filterValues->getAll() as $filterKey => $values) {
            $filter = $filters->get($filterKey);
            if (!$this->isSearchFilter($filter) && !$this->isSpecialFilter($filter)) {
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

    private function isSpecialFilter(?FilterInterface $filter): bool
    {
        return
            $filter instanceof PageNumberFilter
            || $filter instanceof PageSizeFilter
            || $filter instanceof SortFilter
            || $filter instanceof MetaPropertyFilter
            || $filter instanceof FieldsFilter
            || $filter instanceof IncludeFilter;
    }

    private function createSearchFilterError(string $detail, ListContext $context): Error
    {
        return Error::createValidationError(Constraint::FILTER, $detail)
            ->setSource(ErrorSource::createByParameter($this->getSearchFilterName($context)));
    }

    private function getSearchFilterName(ListContext $context): string
    {
        foreach ($context->getFilterValues()->getAll() as $filterKey => $values) {
            if ($this->isSearchFilter($context->getFilters()->get($filterKey))) {
                return $filterKey;
            }
        }

        throw new \LogicException('Neither "searchText" nor "searchQuery" filter was found.');
    }

    private function buildCriteria(Criteria $criteria): Criteria
    {
        $newCriteria = new Criteria();
        $newCriteria->setMaxResults($criteria->getMaxResults());

        return $newCriteria;
    }
}
