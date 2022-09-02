<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SearchBundle\Api\Filter\SimpleSearchFilter;
use Oro\Bundle\SearchBundle\Api\Model\LoadEntityIdsBySearchQuery;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria as SearchCriteria;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Builds a search query object that will be used to get entities by "searchText" filter.
 */
class BuildSearchQuery implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private SearchIndexer $searchIndexer;
    private AbstractSearchMappingProvider $searchMappingProvider;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        SearchIndexer $searchIndexer,
        AbstractSearchMappingProvider $searchMappingProvider,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->searchIndexer = $searchIndexer;
        $this->searchMappingProvider = $searchMappingProvider;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
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

        $expr = $criteria->getWhereExpression();
        if (null === $expr) {
            return;
        }

        $searchText = $this->getSearchText($expr);
        if (!$searchText) {
            return;
        }

        if ($expr instanceof Expr\CompositeExpression && count($expr->getExpressionList()) !== 1) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    'This filter cannot be used together with other filters.'
                )->setSource(ErrorSource::createByParameter($this->getSearchTextFilterName($context->getFilters())))
            );

            return;
        }

        if (!$this->authorizationChecker->isGranted('oro_search')) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    'This filter cannot be used because the search capability is disabled.'
                )->setSource(ErrorSource::createByParameter($this->getSearchTextFilterName($context->getFilters())))
            );

            return;
        }

        $context->setQuery(new LoadEntityIdsBySearchQuery(
            $this->searchIndexer,
            $this->searchMappingProvider,
            $context->getManageableEntityClass($this->doctrineHelper),
            $searchText,
            $criteria->getFirstResult(),
            $criteria->getMaxResults(),
            $criteria->getOrderings(),
            $context->getConfig()->getHasMore()
        ));
        $context->setCriteria($this->buildCriteria($criteria));
    }

    private function getSearchTextFilterName(FilterCollection $filters): string
    {
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            if ($filter instanceof SimpleSearchFilter) {
                return $filterKey;
            }
        }

        throw new \LogicException('The "searchText" filter was not found.');
    }

    private function getSearchText(Expr\Expression $expr): ?string
    {
        if (!$expr instanceof Expr\CompositeExpression) {
            return $this->extractSearchTextFromExpression($expr);
        }

        foreach ($expr->getExpressionList() as $childExpr) {
            $searchText = $this->getSearchText($childExpr);
            if ($searchText) {
                return $searchText;
            }
        }

        return null;
    }

    private function extractSearchTextFromExpression(Expr\Expression $expr): ?string
    {
        if ($expr instanceof Expr\Comparison
            && $expr->getOperator() === Expr\Comparison::CONTAINS
            && $expr->getField() === $this->getAllTextField()
        ) {
            return $expr->getValue()->getValue();
        }

        return null;
    }

    private function getAllTextField(): string
    {
        return SearchCriteria::implodeFieldTypeName(SearchQuery::TYPE_TEXT, SearchIndexer::TEXT_ALL_DATA_FIELD);
    }

    private function buildCriteria(Criteria $criteria): Criteria
    {
        $newCriteria = new Criteria();
        $newCriteria->setMaxResults($criteria->getMaxResults());

        return $newCriteria;
    }
}
