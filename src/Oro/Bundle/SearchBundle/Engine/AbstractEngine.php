<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Oro\Bundle\SearchBundle\Entity\Query as QueryLog;
use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

/**
 * Connector abstract class
 */
abstract class AbstractEngine implements EngineV2Interface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var bool */
    protected $logQueries = false;

    /**
     * @param ManagerRegistry          $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->registry        = $registry;
        $this->eventDispatcher = $eventDispatcher;
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
    public function search(Query $query, array $context = [])
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
}
