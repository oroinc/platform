<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for finding entities for export
 */
class DatagridExportIdFetcher implements ContextAwareInterface
{
    /**
     * @var ServiceLink
     */
    protected $gridManagerLink;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var DatagridInterface
     */
    protected $grid;

    /**
     * @var QueryExecutorInterface
     */
    protected $queryExecutor;

    /**
     * @param ServiceLink $gridManagerLink
     * @param EventDispatcherInterface $eventDispatcher
     * @param QueryExecutorInterface $queryExecutor
     */
    public function __construct(
        ServiceLink $gridManagerLink,
        EventDispatcherInterface $eventDispatcher,
        QueryExecutorInterface $queryExecutor
    ) {
        $this->gridManagerLink = $gridManagerLink;
        $this->eventDispatcher = $eventDispatcher;
        $this->queryExecutor = $queryExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;

        if ($context->hasOption('gridName')) {
            $this->grid = $this->gridManagerLink
                ->getService()
                ->getDatagrid(
                    $context->getOption('gridName'),
                    $context->getOption('gridParameters')
                );
            $context->setValue('columns', $this->grid->getConfig()->offsetGet('columns'));
        } else {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export reader must contain "gridName".'
            );
        }
    }

    /**
     * @return string
     */
    public function getGridRootEntity(): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->grid->getAcceptedDatasource()->getQueryBuilder();

        return $queryBuilder->getRootEntities()[0];
    }

    /**
     * @return array
     */
    public function getGridDataIds()
    {
        /** @var OrmDatasource $dataSource */
        $dataSource = clone $this->grid->getAcceptedDatasource();

        // select only identifier field
        $qb = $dataSource->getQueryBuilder();
        $alias = $qb->getRootAliases()[0];
        $name = $qb->getEntityManager()->getClassMetadata($qb->getRootEntities()[0])->getSingleIdentifierFieldName();

        $this->eventDispatcher->dispatch(
            OrmResultBeforeQuery::NAME,
            new OrmResultBeforeQuery($this->grid, $qb)
        );

        if (!empty($qb->getDQLPart('groupBy')) || !empty($qb->getDQLPart('having'))) {
            return array();
        }

        $qb
            ->indexBy($alias, $alias . '.'. $name)
            ->select($alias . '.' . $name)
            ->setFirstResult(null)
            ->setMaxResults(null);

        if ($this->isOrderedByExpressionOrAlias($qb)) {
            $qb->resetDQLPart('orderBy');
        }

        return $this->queryExecutor->execute(
            $this->grid,
            $qb->getQuery(),
            function ($qb) {
                return array_keys($qb->getArrayResult());
            }
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return bool
     */
    private function isOrderedByExpressionOrAlias(QueryBuilder $queryBuilder): bool
    {
        $orderByParts = $queryBuilder->getDQLPart('orderBy');
        if ($orderByParts === null) {
            return false;
        }

        $aliases = $queryBuilder->getAllAliases();
        /** @var OrderBy $orderByPart */
        foreach ($orderByParts as $orderByPart) {
            foreach ($orderByPart->getParts() as $part) {
                $part = preg_replace('/(ASC|DESC)$/i', '', $part);
                if (!$this->isOrderedByTableField($part, $aliases)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * This function handles only not quoted(delimited) identifiers (i.e. without special characters).
     *
     * @param string $orderBy
     * @param array $aliases
     * @return bool
     */
    private function isOrderedByTableField(string $orderBy, array $aliases): bool
    {
        $parts = explode('.', trim($orderBy));
        if (count($parts) !== 2) {
            return false;
        }

        list($tableName, $fieldName) = $parts;
        if (!in_array($tableName, $aliases, true)) {
            return false;
        }

        return preg_match('/^[\w\_\$]+$/', $fieldName);
    }
}
