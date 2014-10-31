<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;

class StorageDataCollector
{
    /**
     * @var DataGridManager
     */
    protected $datagridManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var Pager
     */
    protected $pager;

    /**
     * @var EntityPaginationStorage
     */
    protected $storage;

    /**
     * @var EntityPaginationManager
     */
    protected $paginationManager;

    /**
     * @param DataGridManager $dataGridManager
     * @param DoctrineHelper $doctrineHelper
     * @param AclHelper $aclHelper
     * @param Pager $pager
     * @param EntityPaginationStorage $storage
     * @param EntityPaginationManager $paginationManager
     */
    public function __construct(
        DataGridManager $dataGridManager,
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        Pager $pager,
        EntityPaginationStorage $storage,
        EntityPaginationManager $paginationManager
    ) {
        $this->datagridManager   = $dataGridManager;
        $this->doctrineHelper    = $doctrineHelper;
        $this->aclHelper         = $aclHelper;
        $this->pager             = $pager;
        $this->storage           = $storage;
        $this->paginationManager = $paginationManager;
    }

    /**
     * @param Request $request
     * @param string $scope
     * @return bool
     */
    public function collect(Request $request, $scope)
    {
        if (!$this->paginationManager->isEnabled()) {
            return false;
        }

        $isDataCollected = false;

        $gridNames = array_keys($request->query->get('grid', []));
        foreach ($gridNames as $gridName) {
            // datagrid manager automatically extracts all required parameters from request
            $dataGrid = $this->datagridManager->getDatagridByRequestParams($gridName);
            if (!$this->paginationManager->isDatagridApplicable($dataGrid)) {
                continue;
            }

            $dataSource = $dataGrid->getDatasource();
            $dataGrid->getAcceptor()->acceptDatasource($dataSource);

            $entityName = $this->getEntityName($dataSource);
            $stateHash = $this->generateStateHash($dataGrid);

            // if entities are not in storage
            if (!$this->storage->hasData($entityName, $stateHash, $scope)) {
                $entitiesLimit = $this->getEntitiesLimit();
                $totalCount = $this->getTotalCount($dataSource, $scope);

                // if grid contains allowed number of entities
                if ($totalCount <= $entitiesLimit) {
                    // collect and set entity IDs
                    $entityIds = $this->getAllEntityIds($dataSource, $scope, $entitiesLimit);
                    $this->storage->setData($entityName, $stateHash, $entityIds, $scope);
                } else {
                    // set empty array as a sign that data is collected, but pagination itself must be disabled
                    $this->storage->setData($entityName, $stateHash, [], $scope);
                }
            }

            $isDataCollected = true;
        }

        return $isDataCollected;
    }

    /**
     * @param OrmDatasource $dataSource
     * @param string $scope
     * @return array
     */
    protected function getAllEntityIds(OrmDatasource $dataSource, $scope)
    {
        $permission = EntityPaginationManager::getPermission($scope);
        $entityName = $this->getEntityName($dataSource);
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);

        $queryBuilder = $dataSource->getQueryBuilder();
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults($this->getEntitiesLimit());

        $query = $this->aclHelper->apply($queryBuilder, $permission);
        $results = $query->execute();

        $entityIds = [];
        foreach ($results as $result) {
            $record = new ResultRecord($result);
            $entityIds[] = $record->getValue($entityIdentifier);
        }

        return $entityIds;
    }

    /**
     * @param OrmDatasource $dataSource
     * @param string $scope
     * @return int
     */
    protected function getTotalCount(OrmDatasource $dataSource, $scope)
    {
        $permission = EntityPaginationManager::getPermission($scope);

        $pager = clone $this->pager;
        $pager->setQueryBuilder($dataSource->getQueryBuilder());
        $pager->setAclPermission($permission);

        return $pager->computeNbResult();
    }

    /**
     * @param OrmDatasource $dataSource
     * @return string
     */
    protected function getEntityName(OrmDatasource $dataSource)
    {
        $queryBuilder = $dataSource->getQueryBuilder();
        $entityName = $queryBuilder->getRootEntities()[0];

        return $this->doctrineHelper->getEntityMetadata($entityName)->getName();
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return string
     */
    protected function generateStateHash(DatagridInterface $dataGrid)
    {
        $state = $dataGrid->getMetadata()->offsetGetByPath('[state]');
        $data = [
            'filters' => !empty($state['filters']) ? $state['filters'] : [],
            'sorters' => !empty($state['sorters']) ? $state['sorters'] : [],
        ];

        return md5(json_encode($data));
    }

    /**
     * @return int
     */
    protected function getEntitiesLimit()
    {
        return $this->paginationManager->getLimit();
    }
}
