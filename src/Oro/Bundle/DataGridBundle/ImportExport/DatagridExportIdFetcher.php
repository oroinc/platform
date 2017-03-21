<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Component\DependencyInjection\ServiceLink;

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
     * @param ServiceLink $gridManagerLink
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ServiceLink $gridManagerLink, EventDispatcherInterface $eventDispatcher)
    {
        $this->gridManagerLink = $gridManagerLink;
        $this->eventDispatcher = $eventDispatcher;
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

        $qb
            ->indexBy($alias, $alias . '.'. $name)
            ->select($alias . '.' . $name, $alias . '.' . $name)
            ->setFirstResult(null)
            ->setMaxResults(null)
        ;

        return array_keys($qb->getQuery()->getArrayResult());
    }
}
