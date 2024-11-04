<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Query\Query as SearchQueryBuilder;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;

/**
 * The API manager to find entities associated with the email activity.
 */
class EmailActivitySearchApiEntityManager extends ActivitySearchApiEntityManager
{
    public function __construct(
        string $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        SearchIndexer $searchIndexer
    ) {
        parent::__construct($om, $activityManager, $searchIndexer);
        $this->setClass($class);
    }

    #[\Override]
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $searchQueryBuilder = parent::getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);

        if (!empty($criteria['emails'])) {
            $this->prepareSearchEmailCriteria($searchQueryBuilder, $criteria['emails']);
        }

        return $searchQueryBuilder;
    }

    /**
     * @param SearchQueryBuilder $searchQueryBuilder
     * @param string[]           $emails
     */
    protected function prepareSearchEmailCriteria(SearchQueryBuilder $searchQueryBuilder, $emails = [])
    {
        $searchCriteria = $searchQueryBuilder->getCriteria();
        $emailString = implode(' ', $emails);
        $searchCriteria->andWhere(
            $searchCriteria->expr()->contains('email', $emailString)
        );
    }

    /**
     * Gets search results.
     *
     * @param int   $limit
     * @param int   $page
     * @param array $criteria
     * @param null  $orderBy
     * @param array $joins
     *
     * @return array
     */
    public function getSearchResult($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $searchQueryBuilder = $this->getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);

        $searchResult = $this->searchIndexer->query($searchQueryBuilder);

        $result = [
            'result'     => [],
            'totalCount' =>
                function () use ($searchResult) {
                    return $searchResult->getRecordsCount();
                }
        ];

        if ($searchResult->count() > 0) {
            $result['result'] = $this->convertSearchResultToEntityList($searchResult);
        }

        return $result;
    }

    protected function convertSearchResultToEntityList(SearchResult $searchResult): array
    {
        $result = [];
        /** @var SearchResultItem $item */
        foreach ($searchResult as $item) {
            $result[] = [
                'id' => $item->getRecordId(),
                'entity' => $item->getEntityName(),
                'title' => $item->getSelectedData()['name']
            ];
        }

        return $result;
    }
}
