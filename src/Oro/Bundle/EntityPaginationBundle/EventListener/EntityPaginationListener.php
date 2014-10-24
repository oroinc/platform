<?php

namespace Oro\Bundle\EntityPaginationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DataGridManager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

class EntityPaginationListener
{
    /** @var DataGridManager */
    protected $dataGridManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityPaginationStorage */
    protected $storage;

    /**
     * @param DataGridManager $dataGridManager
     * @param DoctrineHelper $doctrineHelper
     * @param EntityPaginationStorage $storage
     */
    public function __construct(
        DataGridManager $dataGridManager,
        DoctrineHelper $doctrineHelper,
        EntityPaginationStorage $storage
    ) {
        $this->dataGridManager = $dataGridManager;
        $this->doctrineHelper  = $doctrineHelper;
        $this->storage         = $storage;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        if (!$this->storage->isEnabled()) {
            return;
        }
        
        $dataGrid = $event->getDatagrid();

        // if entity pagination is enabled on current grid
        if ($dataGrid->getConfig()->offsetGetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH) === true) {
            $entityName    = $this->getEntityName($dataGrid);
            $totalCount    = $this->getTotalCount($dataGrid);
            $stateHash     = $this->generateStateHash($dataGrid, $totalCount);
            $entitiesLimit = $this->getEntitiesLimit();

            // if grid contains allowed number of entities and these entities are not in storage
            if ($totalCount <= $entitiesLimit && !$this->storage->hasData($entityName, $stateHash)) {
                // set empty storage to avoid recursion during calculation of all entity IDs
                $this->storage->setData($entityName, $stateHash, []);
                $entityIds = $this->getAllEntityIds($dataGrid, $totalCount);
                $this->storage->setData($entityName, $stateHash, $entityIds);
            }
        }
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return array
     */
    protected function getAllEntityIds(DatagridInterface $dataGrid)
    {
        $state = $this->getDataGridState($dataGrid);
        $entitiesLimit = $this->getEntitiesLimit();
        $parameters = [
            OrmFilterExtension::FILTER_ROOT_PARAM  => !empty($state['filters']) ? $state['filters'] : [],
            OrmSorterExtension::SORTERS_ROOT_PARAM => !empty($state['sorters']) ? $state['sorters'] : [],
            PagerInterface::PAGER_ROOT_PARAM       => [
                PagerInterface::PAGE_PARAM     => 1,
                PagerInterface::PER_PAGE_PARAM => $entitiesLimit
            ],
        ];

        $entityName = $this->getEntityName($dataGrid);
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);

        $fullDataGrid = $this->dataGridManager->getDataGrid($dataGrid->getName(), $parameters);
        $records = $fullDataGrid->getData()->toArray();

        $entityIds = [];
        foreach ($records['data'] as $record) {
            $entityIds[] = $record[$entityIdentifier];
        }

        return $entityIds;
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return int
     */
    protected function getTotalCount(DatagridInterface $dataGrid)
    {
        $result = ResultsObject::create([]);
        $dataGrid->getAcceptor()->acceptResult($result);

        return (int)$result->offsetGetByPath(PagerInterface::TOTAL_PATH_PARAM);
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

    /**
     * @param DatagridInterface $dataGrid
     * @param int $totalCount
     * @return string
     */
    protected function generateStateHash(DatagridInterface $dataGrid, $totalCount)
    {
        $state = $this->getDataGridState($dataGrid);
        $data = [
            'filters' => !empty($state['filters']) ? $state['filters'] : [],
            'sorters' => !empty($state['sorters']) ? $state['sorters'] : [],
            'total'   => $totalCount
        ];

        return md5(json_encode($data));
    }

    /**
     * @return bool
     */
    protected function getEntitiesLimit()
    {
        return $this->storage->getLimit();
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return array
     */
    protected function getDataGridState(DatagridInterface $dataGrid)
    {
        return $dataGrid->getMetadata()->offsetGetByPath('[state]');
    }
}
