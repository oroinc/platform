<?php

namespace Oro\Bundle\EntityBundle\DataCollector\Analyzer;

class DuplicateQueryAnalyzer
{
    /**
     * @var array
     */
    protected $queries = [];

    /**
     * @param string $sql
     * @param array $parameters
     */
    public function addQuery($sql, array $parameters = [])
    {
        $this->queries[] = [
            'sql' => $sql,
            'parameters' => $parameters,
        ];
    }

    /**
     * @return int
     */
    public function getQueriesCount()
    {
        return count($this->queries);
    }

    /**
     * @return array
     */
    public function getIdenticalQueries()
    {
        $identicalQueriesCounter = [];
        $identicalQueries = [];
        foreach ($this->queries as $query) {
            $queryKey = $this->generateQueryKey($query['sql'], $query['parameters']);

            if (!isset($identicalQueriesCounter[$queryKey])) {
                $identicalQueriesCounter[$queryKey] = 0;
            }
            $identicalQueriesCounter[$queryKey]++;

            if ($identicalQueriesCounter[$queryKey] > 1) {
                $identicalQueries[$queryKey] = [
                    'sql' => $query['sql'],
                    'parameters' => $query['parameters'],
                    'count' => $identicalQueriesCounter[$queryKey],
                ];
            }
        }

        return array_values($identicalQueries);
    }

    /**
     * @return array
     */
    public function getSimilarQueries()
    {
        $sameParamsCounter = [];
        $similarQueries = [];
        foreach ($this->queries as $query) {
            if (count($query['parameters']) === 0) {
                continue;
            }
            $queryKey = $this->generateSqlKey($query['sql']);
            $queryKeyWithParameters = $this->generateParametersKey($query['parameters']);

            if (!isset($sameParamsCounter[$queryKey][$queryKeyWithParameters])) {
                $sameParamsCounter[$queryKey][$queryKeyWithParameters] = 0;
            }
            $sameParamsCounter[$queryKey][$queryKeyWithParameters]++;

            if (count($sameParamsCounter[$queryKey]) > 1) {
                $similarQueries[$queryKey] = [
                    'sql' => $query['sql'],
                    'count' => array_sum($sameParamsCounter[$queryKey]),
                ];
            }
        }

        return array_values($similarQueries);
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @return string
     */
    protected function generateQueryKey($sql, array $parameters)
    {
        return $this->generateSqlKey($sql).':'.$this->generateParametersKey($parameters);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function generateSqlKey($value)
    {
        return sha1($value);
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateParametersKey(array $value)
    {
        return sha1(serialize($value));
    }
}
