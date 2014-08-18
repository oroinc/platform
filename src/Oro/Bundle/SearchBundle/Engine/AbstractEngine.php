<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SearchBundle\Entity\Query as QueryLog;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Connector abstract class
 */
abstract class AbstractEngine implements EngineInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var bool
     */
    protected $logQueries;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * Init entity manager
     *
     * @param EntityManager   $em
     * @param EventDispatcher $dispatcher
     * @param boolean         $logQueries
     */
    public function __construct(EntityManager $em, EventDispatcher $dispatcher, $logQueries)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->logQueries = $logQueries;
    }

    /**
     * Insert or update record
     *
     * @param object $entity
     * @param bool   $realtime
     * @param bool   $needToCompute
     *
     * @return mixed
     */
    abstract public function save($entity, $realtime = true, $needToCompute = false);

    /**
     * Insert or update record
     *
     * @param object $entity
     * @param bool   $realtime
     *
     * @return mixed
     */
    abstract public function delete($entity, $realtime = true);

    /**
     * Reload search index
     *
     * @return int Count of index records
     */
    abstract public function reindex();

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
     * Search query with query builder
     *
     * @param Query $query
     * @return Result
     */
    public function search($query)
    {
        $searchResult = $this->doSearch($query);
        foreach ($searchResult['results'] as $resultRecord) {
            $this->dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($resultRecord));
        }
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
        $logRecord = new QueryLog;
        $logRecord->setEntity(serialize($result->getQuery()->getFrom()));
        $logRecord->setQuery(serialize($result->getQuery()->getOptions()));
        $logRecord->setResultCount($result->count());

        $this->em->persist($logRecord);
        $this->em->flush();
    }
}
