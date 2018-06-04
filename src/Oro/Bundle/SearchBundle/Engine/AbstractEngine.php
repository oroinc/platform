<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Entity\Query as QueryLog;
use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Abstract standard search engine
 */
abstract class AbstractEngine implements EngineInterface
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
     *   'results' - array of Oro\Bundle\SearchBundle\Query\Result\Item objects or callable to get it
     *   'records_count' - count of records without limit parameters in query or callable to get it
     *   'aggregated_data' - results of aggregating operations or callable to get it
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
        $result = $this->buildResult($query, $searchResult);

        if ($this->logQueries) {
            $this->logQuery($result);
        }

        return $result;
    }

    /**
     * @param Query $query
     * @param array $data
     * @return Result
     */
    protected function buildResult(Query $query, array $data)
    {
        return new Result(
            $query,
            $data['results'],
            $data['records_count'],
            $data['aggregated_data']
        );
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
