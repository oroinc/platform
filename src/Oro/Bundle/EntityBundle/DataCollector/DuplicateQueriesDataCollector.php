<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Doctrine\Bundle\DoctrineBundle\Middleware\BacktraceDebugDataHolder;
use Oro\Bundle\EntityBundle\DataCollector\Analyzer\DuplicateQueryAnalyzer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collects identical and similar queries number
 */
class DuplicateQueriesDataCollector extends DataCollector
{
    private BacktraceDebugDataHolder $debugDataHolder;

    public function __construct(BacktraceDebugDataHolder $debugDataHolder)
    {
        $this->debugDataHolder = $debugDataHolder;
        $this->reset();
    }

    #[\Override]
    public function collect(Request $request, Response $response, ?\Throwable $exception = null)
    {
        $data = $this->debugDataHolder->getData();
        foreach ($data as $connectionName => $queries) {
            $queryAnalyser = new DuplicateQueryAnalyzer();
            foreach ($queries as $query) {
                $queryAnalyser->addQuery($query['sql'], (array)($query['params'] ?? []));
            }

            $this->data['queriesCount'][$connectionName] = $queryAnalyser->getQueriesCount();
            $this->data['identical'][$connectionName] = $queryAnalyser->getIdenticalQueries();
            $this->data['similar'][$connectionName] = $queryAnalyser->getSimilarQueries();
        }
    }

    #[\Override]
    public function getName(): string
    {
        return 'duplicate_queries';
    }

    /**
     * @return mixed
     */
    public function getQueriesCount()
    {
        return array_sum($this->data['queriesCount']);
    }

    /**
     * @return mixed
     */
    public function getIdenticalQueries()
    {
        return $this->data['identical'];
    }

    /**
     * @return number
     */
    public function getIdenticalQueriesCount()
    {
        return $this->countGroupedQueries($this->data['identical']);
    }

    /**
     * @return mixed
     */
    public function getSimilarQueries()
    {
        return $this->data['similar'];
    }

    /**
     * @return number
     */
    public function getSimilarQueriesCount()
    {
        return $this->countGroupedQueries($this->data['similar']);
    }

    /**
     * @param array $queries
     * @return number
     */
    protected function countGroupedQueries(array $queries)
    {
        return array_sum(array_map('count', $queries));
    }

    #[\Override]
    public function reset()
    {
        $this->data = [
            'queriesCount' => [],
            'identical' => [],
            'similar' => [],
        ];
    }
}
