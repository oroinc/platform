<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\SearchBundle\Entity\Query as QueryLog;
use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Command\IndexCommand;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Connector abstract class
 */
abstract class AbstractEngine implements EngineInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ObjectMapper
     */
    protected $mapper;

    /**
     * @var bool
     */
    protected $logQueries = false;

    /**
     * @param ManagerRegistry          $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper           $doctrineHelper
     * @param ObjectMapper             $mapper
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper
    ) {
        $this->registry        = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper  = $doctrineHelper;
        $this->mapper          = $mapper;
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
        $result = new Result($query, $searchResult['results'], $searchResult['records_count']);

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
        $logRecord->setQuery(serialize($result->getQuery()->getOptions()));
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

        $entityIdentifiers = array();
        foreach ($entities as $entity) {
            $class = $this->doctrineHelper->getEntityClass($entity);
            $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $entityIdentifiers[$class][] = $identifier;
        }

        $jobs = array();
        foreach ($entityIdentifiers as $class => $identifiers) {
            $jobs[] = new Job(IndexCommand::NAME, array_merge(array($class), $identifiers));
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
     * @param string $entityName
     * @return int
     */
    protected function reindexSingleEntity($entityName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $queryBuilder = $entityManager->getRepository($entityName)->createQueryBuilder('entity');
        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(static::BATCH_SIZE);

        $itemsCount = 0;
        $entities = array();

        foreach ($iterator as $entity) {
            $entities[] = $entity;
            $itemsCount++;

            if (0 == $itemsCount % static::BATCH_SIZE) {
                $this->save($entities, true);
                $entityManager->clear();
                $entities = array();
            }
        }

        if ($itemsCount % static::BATCH_SIZE > 0) {
            $this->save($entities, true);
            $entityManager->clear();
        }

        return $itemsCount;
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
        $entityClass = ClassUtils::getClass($entity);
        $fields = $this->mapper->getEntityMapParameter($entityClass, 'title_fields');
        if ($fields) {
            $title = array();
            foreach ($fields as $field) {
                $title[] = $this->mapper->getFieldValue($entity, $field);
            }
        } else {
            $title = array((string) $entity);
        }

        return implode(' ', $title);
    }

    /**
     * @param object|array $entity
     * @return array
     */
    protected function getEntitiesArray($entity)
    {
        if (!$entity) {
            return array();
        }

        return is_array($entity) ? $entity : array($entity);
    }
}
