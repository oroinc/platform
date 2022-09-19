<?php

namespace Oro\Bundle\PlatformBundle\MaterializedView;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides common methods for fetching data from the materialized view.
 */
class MaterializedViewRepository
{
    private EntityManagerInterface $entityManager;

    private string $materializedViewName;

    public function __construct(EntityManagerInterface $entityManager, string $materializedViewName)
    {
        $this->entityManager = $entityManager;
        $this->materializedViewName = $materializedViewName;
    }

    public function createQueryBuilder(string $rootAlias = 'matview'): QueryBuilder
    {
        $connection = $this->entityManager->getConnection();
        $queryBuilder = new QueryBuilder($connection);

        QueryBuilderUtil::checkIdentifier($this->materializedViewName);
        QueryBuilderUtil::checkIdentifier($rootAlias);
        $queryBuilder->from($this->materializedViewName, $rootAlias);

        return $queryBuilder;
    }

    public function getRowsCount(): int
    {
        return (int)$this->createQueryBuilder()
            ->select('COUNT(1)')
            ->execute()
            ->fetchOne();
    }
}
