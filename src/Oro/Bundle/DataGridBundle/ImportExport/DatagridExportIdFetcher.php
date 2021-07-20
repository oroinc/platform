<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
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
        // select only identifier field
        /** @var QueryBuilder $qb */
        $qb = clone $this->grid->getAcceptedDatasource()->getQueryBuilder();
        $alias = $qb->getRootAliases()[0];
        $name = $qb->getEntityManager()->getClassMetadata($qb->getRootEntities()[0])->getSingleIdentifierFieldName();

        $this->eventDispatcher->dispatch(
            new OrmResultBeforeQuery($this->grid, $qb),
            OrmResultBeforeQuery::NAME
        );

        $field = $alias . '.' . $name;
        if (!empty($qb->getDQLPart('groupBy'))) {
            if (empty($qb->getDQLPart('having'))) {
                // If there is no having we may select unique IDs only without grouping.
                $qb->select($field)
                    ->distinct(true)
                    ->resetDQLPart('groupBy');
            } else {
                // When there is a having clause, to select IDs, we should add them to the query
                $qb->addSelect($field)
                    ->addGroupBy($field);
            }
        } else {
            // When there is no group by we may select unique IDs only
            $qb->select($field)
                ->distinct(true);
        }
        $qb->indexBy($alias, $field)
            ->resetDQLPart('orderBy')
            ->setFirstResult(null)
            ->setMaxResults(null);

        return $this->queryExecutor->execute(
            $this->grid,
            $qb->getQuery(),
            function ($qb) {
                // indexBy forces to use a given field as a result array key. We have chosen an ID to be the index.
                $ids = array_keys($qb->getArrayResult());

                return array_unique($ids);
            }
        );
    }
}
