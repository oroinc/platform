<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SearchBundle\Query\Query as SearchQueryBuilder;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

class EmailActivitySearchApiEntityManager extends ActivitySearchApiEntityManager
{
    /** @var EntityNameResolver  */
    protected $entityNameResolver;

    /**
     * @param string             $class
     * @param ObjectManager      $om
     * @param ActivityManager    $activityManager
     * @param SearchIndexer      $searchIndexer
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        SearchIndexer $searchIndexer,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct($om, $activityManager, $searchIndexer);
        $this->setClass($class);
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
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
            $entities = $this->getEmailAssociatedEntitiesQueryBuilder($searchResult)->getQuery()->getResult();
            $result['result'] = $entities;
        }

        return $result;
    }

    /**
     * Returns a query builder that contains entities from the search result in which titles replaced with
     * text representation of appropriate entities.
     *
     * @todo: This functionality should be removed in the BAP-8995.
     *
     * @param SearchResult $searchResult
     *
     * @return SqlQueryBuilder
     */
    protected function getEmailAssociatedEntitiesQueryBuilder(SearchResult $searchResult)
    {
        /** @var EntityManager $em */
        $em = $this->getObjectManager();

        $selectStmt = null;
        $subQueries = [];
        foreach ($this->getAssociatedEntitiesFilters($searchResult) as $entityClass => $ids) {
            $nameExpr = $this->entityNameResolver->getNameDQL($entityClass, 'e');
            /** @var QueryBuilder $subQb */
            $subQb    = $em->getRepository($entityClass)->createQueryBuilder('e')
                ->select(
                    sprintf(
                        'e.id AS id, \'%s\' AS entityClass, ' . ($nameExpr ?: '\'\'') . ' AS entityTitle',
                        str_replace('\'', '\'\'', $entityClass)
                    )
                );
            $subQb->where(
                $subQb->expr()->in('e.id', $ids)
            );

            $subQuery     = $subQb->getQuery();
            $subQueries[] = QueryUtils::getExecutableSql($subQuery);

            if (empty($selectStmt)) {
                $mapping    = QueryUtils::parseQuery($subQuery)->getResultSetMapping();
                $selectStmt = sprintf(
                    'entity.%s AS id, entity.%s AS entity, entity.%s AS title',
                    QueryUtils::getColumnNameByAlias($mapping, 'id'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityClass'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityTitle')
                );
            }
        }

        $rsm = QueryUtils::createResultSetMapping($em->getConnection()->getDatabasePlatform());
        $rsm
            ->addScalarResult('id', 'id', Type::INTEGER)
            ->addScalarResult('entity', 'entity')
            ->addScalarResult('title', 'title');
        $qb = new SqlQueryBuilder($em, $rsm);
        $qb
            ->select($selectStmt)
            ->from('(' . implode(' UNION ALL ', $subQueries) . ')', 'entity');

        return $qb;
    }

    /**
     * Extracts ids of the entities from a given search result.
     *
     * @param SearchResult $searchResult
     *
     * @return array example: ['Acme\Entity\Activity' => [1, 2, 3], ...]
     */
    protected function getAssociatedEntitiesFilters(SearchResult $searchResult)
    {
        $filters = [];
        /** @var SearchResultItem $item */
        foreach ($searchResult as $item) {
            $entityClass = $item->getEntityName();
            if (!isset($filters[$entityClass])) {
                $filters[$entityClass] = [];
            }
            $filters[$entityClass][] = $item->getRecordId();
        }

        return $filters;
    }
}
