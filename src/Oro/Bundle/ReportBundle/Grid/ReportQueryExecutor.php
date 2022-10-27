<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * This implementation of the query executor uses a separate DBAL connection to execute report queries.
 */
class ReportQueryExecutor implements QueryExecutorInterface
{
    /** @var QueryExecutorInterface */
    private $baseQueryExecutor;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $reportConnectionName;

    /** @var string[] */
    private $reportDatagridPrefixes;

    /**
     * @param QueryExecutorInterface $baseQueryExecutor
     * @param ManagerRegistry        $doctrine
     * @param string                 $reportConnectionName
     * @param string[]               $reportDatagridPrefixes
     */
    public function __construct(
        QueryExecutorInterface $baseQueryExecutor,
        ManagerRegistry $doctrine,
        string $reportConnectionName,
        array $reportDatagridPrefixes
    ) {
        if (!$reportConnectionName) {
            throw new \InvalidArgumentException('The report connection name must be specified.');
        }

        $this->baseQueryExecutor = $baseQueryExecutor;
        $this->doctrine = $doctrine;
        $this->reportConnectionName = $reportConnectionName;
        $this->reportDatagridPrefixes = $reportDatagridPrefixes;
        $this->reportDatagridPrefixes[] = Report::GRID_PREFIX;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(DatagridInterface $datagrid, Query $query, $executeFunc = null)
    {
        if (!$this->isApplicable($datagrid->getName())) {
            return $this->baseQueryExecutor->execute($datagrid, $query, $executeFunc);
        }

        $em = $query->getEntityManager();
        $connection = $em->getConnection();
        // substitute the connection (unfortunately the reflection is only possible way to do it)
        $setConnectionClosure = \Closure::bind(
            function ($em, $connection) {
                $em->conn = $connection;
            },
            null,
            EntityManager::class
        );
        $setConnectionClosure($em, $this->doctrine->getConnection($this->reportConnectionName));
        try {
            return $this->baseQueryExecutor->execute($datagrid, $query, $executeFunc);
        } finally {
            // restore the original connection
            $setConnectionClosure($em, $connection);
        }
    }

    private function isApplicable(string $gridName): bool
    {
        foreach ($this->reportDatagridPrefixes as $prefix) {
            if (str_starts_with($gridName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
