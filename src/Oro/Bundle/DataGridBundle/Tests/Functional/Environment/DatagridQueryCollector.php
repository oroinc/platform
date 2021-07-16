<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Environment;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;

/**
 * The decorator that collects all executed datagrid queries.
 */
class DatagridQueryCollector implements QueryExecutorInterface
{
    /** @var QueryExecutorInterface */
    private $queryExecutor;

    /** @var array [datagrid name => [query, ...], ...] */
    private $executedQueries = [];

    /** @var string[] */
    private $datagridNames = [];

    public function __construct(QueryExecutorInterface $queryExecutor)
    {
        $this->queryExecutor = $queryExecutor;
    }

    /**
     * Enables collecting of executed queries for the given datagrid.
     */
    public function enable(string $datagridName): void
    {
        $this->datagridNames[] = $datagridName;
    }

    /**
     * Disables collecting of executed queries for all datagrids.
     */
    public function disable(): void
    {
        $this->datagridNames = [];
        $this->executedQueries = [];
    }

    /**
     * Removes all executed queries.
     */
    public function clear(): void
    {
        $this->executedQueries = [];
    }

    /**
     * Gets all executed queries.
     *
     * @return array [datagrid name => [query, ...], ...]
     */
    public function getExecutedQueries(): array
    {
        return $this->executedQueries;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(DatagridInterface $datagrid, Query $query, $executeFunc = null)
    {
        $datagridName = $datagrid->getName();
        if (in_array($datagridName, $this->datagridNames, true)) {
            $this->executedQueries[$datagridName][] = $query->getDQL();
        }

        return $this->queryExecutor->execute($datagrid, $query, $executeFunc);
    }
}
