<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Filter\FieldFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria as SearchCriteria;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * A filter that can be used to filter data by "all_text" field in a search index.
 */
class SimpleSearchFilter extends StandaloneFilter implements FieldFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        if (null === $value) {
            return;
        }

        $criteria->andWhere($this->createExpression($value->getValue()));
    }

    private function createExpression(string $searchText): Expression
    {
        return SearchCriteria::expr()->contains(
            SearchCriteria::implodeFieldTypeName(SearchQuery::TYPE_TEXT, SearchIndexer::TEXT_ALL_DATA_FIELD),
            $searchText
        );
    }
}
