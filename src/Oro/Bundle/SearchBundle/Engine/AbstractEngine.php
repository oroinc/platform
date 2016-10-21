<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\OrderBy;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Command\IndexCommand;
use Oro\Bundle\SearchBundle\Entity\Query as QueryLog;
use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;

/**
 * Connector abstract class
 */
abstract class AbstractEngine implements EngineInterface
{
    const BATCH_SIZE = 1000;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var EntityTitleResolverInterface */
    protected $entityTitleResolver;

    /** @var bool */
    protected $logQueries = false;

    /** @var \Iterator[]|\Countable[] */
    protected $iteratorCache = [];

    /**
     * @param ManagerRegistry               $registry
     * @param EventDispatcherInterface      $eventDispatcher
     * @param DoctrineHelper                $doctrineHelper
     * @param ObjectMapper                  $mapper
     * @param EntityTitleResolverInterface  $entityTitleResolver
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper,
        EntityTitleResolverInterface $entityTitleResolver
    ) {
        $this->registry             = $registry;
        $this->eventDispatcher      = $eventDispatcher;
        $this->doctrineHelper       = $doctrineHelper;
        $this->mapper               = $mapper;
        $this->entityTitleResolver  = $entityTitleResolver;
    }

    /**
     * @param bool $logQueries
     */
    public function setLogQueries($logQueries)
    {
        $this->logQueries = $logQueries;
    }

    /**
     * Search query with query builder
     * Must return array
     * array(
     *   'results' - array of Oro\Bundle\SearchBundle\Query\Result\Item objects
     *   'records_count' - count of records without limit parameters in query
     * )
     *
     * @param Query $query
     *
     * @return array
     */
    abstract protected function doSearch(Query $query);

    /**
     * {@inheritdoc}
     */
    public function search(Query $query)
    {
        $event = new BeforeSearchEvent($query);
        $this->eventDispatcher->dispatch(BeforeSearchEvent::EVENT_NAME, $event);
        $query = $event->getQuery();

        // search must be performed as fast as possible and it might return lots of entities (10M and more)
        // it's important to not trigger any additional or processing for all entities here
        $searchResult = $this->doSearch($query);
        $result       = new Result($query, $searchResult['results'], $searchResult['records_count']);

        if ($this->logQueries) {
            $this->logQuery($result);
        }

        return $result;
    }

    /**
     * Log query
     *
     * @param Result $result
     */
    protected function logQuery(Result $result)
    {
        $entityManager = $this->registry->getManagerForClass('Oro\Bundle\SearchBundle\Entity\Query');

        $logRecord = new QueryLog;
        $logRecord->setEntity(implode(',', array_values($result->getQuery()->getFrom())));
        $logRecord->setQuery(serialize($result->getQuery()->getCriteria()));
        $logRecord->setResultCount($result->count());

        $entityManager->persist($logRecord);
        $entityManager->flush($logRecord);
    }

    /**
     * Add index task to queue
     *
     * @param object|array $entity
     * @return Job[]
     */
    protected function createQueueJobs($entity)
    {
        $entities = $this->getEntitiesArray($entity);

        $entityIdentifiers = [];
        foreach ($entities as $entity) {
            $class                       = $this->doctrineHelper->getEntityClass($entity);
            $identifier                  = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $entityIdentifiers[$class][] = $identifier;
        }

        $jobs = [];
        foreach ($entityIdentifiers as $class => $identifiers) {
            $jobs[] = new Job(IndexCommand::NAME, array_merge([$class], $identifiers));
        }

        return $jobs;
    }

    /**
     * Add index tasks to job queue
     *
     * @param object|array $entity
     */
    protected function scheduleIndexation($entity)
    {
        $entityManager = $this->registry->getManagerForClass('JMSJobQueueBundle:Job');

        $jobs = $this->createQueueJobs($entity);

        if ($jobs) {
            foreach ($jobs as $job) {
                $entityManager->persist($job);
            }

            $entityManager->flush();
        }
    }

    /**
     * @param string       $entityName
     * @param integer|null $offset
     * @param integer|null $limit
     *
     * @return int
     */
    protected function reindexSingleEntity($entityName, $offset = null, $limit = null)
    {
        $iterator = $this->createIterator($entityName, $offset, $limit);

        $itemsCount = 0;
        foreach ($iterator as $entity) {
            $this->recordProcessed();
            $itemsCount++;
        }

        return $itemsCount;
    }

    /**
     * Called each time record was processed to keep track of progress
     */
    protected function recordProcessed()
    {
    }

    /**
     * @return int
     */
    protected function getNumberOfRecordsToReindex($entityName, $offset = null, $limit = null)
    {
        return count($this->createIterator($entityName, $offset, $limit, true));
    }

    /**
     * @param string       $entityName
     * @param integer|null $offset
     * @param integer|null $limit
     * @param bool         $cache
     *
     * @return \Iterator|\Countable
     */
    protected function createIterator($entityName, $offset = null, $limit = null, $cache = false)
    {
        $key = $this->createIteratorCacheKey($entityName, $offset, $limit);
        if (!isset($this->iteratorCache[$key])) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->registry->getManagerForClass($entityName);
            $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

            $pk = $entityManager->getClassMetadata($entityName)->getIdentifier();

            $orderingsExpr = new OrderBy();
            foreach ($pk as $fieldName) {
                $orderingsExpr->add('entity.' . $fieldName);
            }

            $queryBuilder = $entityManager->getRepository($entityName)
                ->createQueryBuilder('entity')
                ->orderBy($orderingsExpr);

            if (null !== $offset) {
                $queryBuilder->setFirstResult($offset);
            }
            if (null !== $limit) {
                $queryBuilder->setMaxResults($limit);
            }

            $iterator = new BufferedQueryResultIterator($queryBuilder);
            $iterator->setBufferSize(static::BATCH_SIZE);
            $iterator->setPageLoadedCallback(function (array $entities) use ($entityManager) {
                $this->save($entities, true);
                $entityManager->clear();
                gc_collect_cycles();

                return $entities;
            });

            if ($cache) {
                $this->iteratorCache[$key] = $iterator;
            }
        } else {
            $iterator = $this->iteratorCache[$key];
        }

        if (!$cache) {
            unset($this->iteratorCache[$key]);
        }

        return $iterator;
    }

    /**
     * @param string       $entityName
     * @param integer|null $offset
     * @param integer|null $limit
     *
     * @return string
     */
    protected function createIteratorCacheKey($entityName, $offset = null, $limit = null)
    {
        return sprintf('%s.%d.%d', $entityName, $offset, $limit);
    }

    /**
     * Get entity string
     *
     * @param object $entity
     *
     * @return string
     */
    protected function getEntityTitle($entity)
    {
        return $this->entityTitleResolver->resolve($entity);
    }

    /**
     * @param object|array $entity
     * @return array
     */
    protected function getEntitiesArray($entity)
    {
        if (!$entity) {
            return [];
        }

        return is_array($entity) ? $entity : [$entity];
    }
}
