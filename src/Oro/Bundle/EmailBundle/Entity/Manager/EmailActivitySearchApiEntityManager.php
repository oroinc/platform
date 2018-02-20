<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Query\Query as SearchQueryBuilder;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;

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

        $qb = new UnionQueryBuilder($em);
        $qb
            ->addSelect('id', 'id', Type::INTEGER)
            ->addSelect('entityClass', 'entity')
            ->addSelect('entityTitle', 'title');
        foreach ($this->getAssociatedEntitiesFilters($searchResult) as $entityClass => $ids) {
            $subQb = $em->getRepository($entityClass)->createQueryBuilder('e');
            $subQb
                ->select(
                    'e.id AS id',
                    (string)$subQb->expr()->literal($entityClass) . ' AS entityClass',
                    $this->entityNameResolver->prepareNameDQL(
                        $this->entityNameResolver->getNameDQL($entityClass, 'e'),
                        true
                    ) . ' AS entityTitle'
                );
            $subQb->where($subQb->expr()->in('e.id', $ids));
            $qb->addSubQuery($subQb->getQuery());
        }

        return $qb->getQueryBuilder();
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
