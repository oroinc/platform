<?php

namespace Oro\Bundle\EntityPaginationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;

class EntityPaginationListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityPaginationStorage */
    protected $storage;

    /** @var EntityPaginationManager  */
    protected $paginationManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityPaginationStorage $storage
     * @param EntityPaginationManager $paginationManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityPaginationStorage $storage,
        EntityPaginationManager $paginationManager
    ) {
        $this->doctrineHelper    = $doctrineHelper;
        $this->storage           = $storage;
        $this->paginationManager = $paginationManager;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        if (!$this->paginationManager->isEnabled()) {
            return;
        }
        
        $dataGrid = $event->getDatagrid();
        if (!$this->paginationManager->isDatagridApplicable($dataGrid)) {
            return;
        }

        // clear all data as long as we can't guarantee that storage data is valid
        $entityName = $this->getEntityName($dataGrid);
        $this->storage->clearData($entityName);
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return string
     */
    protected function getEntityName(DatagridInterface $dataGrid)
    {
        /** @var OrmDatasource $dataSource */
        $dataSource = $dataGrid->getDatasource();
        $queryBuilder = $dataSource->getQueryBuilder();
        $entityName = $queryBuilder->getRootEntities()[0];

        return $this->doctrineHelper->getEntityMetadata($entityName)->getName();
    }
}
