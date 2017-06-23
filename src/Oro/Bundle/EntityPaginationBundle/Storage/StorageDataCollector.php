<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\HttpFoundation\Request;

class StorageDataCollector
{
    const PAGINGATION_PARAM = 'entity_pagination';

    /**
     * @var ServiceLink
     */
    protected $datagridManagerLink;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityPaginationStorage
     */
    protected $storage;

    /**
     * @var EntityPaginationManager
     */
    protected $paginationManager;

    /**
     * @param ServiceLink $dataGridManagerLink Link Used instead of manager because of performance reasons
     * @param DoctrineHelper $doctrineHelper
     * @param EntityPaginationStorage $storage
     * @param EntityPaginationManager $paginationManager
     */
    public function __construct(
        ServiceLink $dataGridManagerLink,
        DoctrineHelper $doctrineHelper,
        EntityPaginationStorage $storage,
        EntityPaginationManager $paginationManager
    ) {
        $this->datagridManagerLink = $dataGridManagerLink;
        $this->doctrineHelper = $doctrineHelper;
        $this->storage = $storage;
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

        foreach ($this->getGridNames($request) as $gridName) {
            $dataGrid = $this->getGrid(
                $gridName,
                [
                    Manager::REQUIRE_ALL_EXTENSIONS => false,
                    self::PAGINGATION_PARAM => true
                ]
            );
            if (null === $dataGrid || !$this->paginationManager->isDatagridApplicable($dataGrid)) {
                continue;
            }

            $entityName = $this->getEntityNameFromConfig($dataGrid);
            $stateHash = $this->generateStateHash($dataGrid);

            // if entities are not in storage
            if (!$this->storage->hasData($entityName, $stateHash, $scope)) {
                $initialScope = $dataGrid->getScope();
                $dataGrid->setScope($scope);
                try {
                    $entitiesLimit = $this->getEntitiesLimit();
                    $totalCount = $this->getTotalCount($dataGrid, $scope);

                    // if grid contains allowed number of entities
                    if ($totalCount <= $entitiesLimit) {
                        /** @var OrmDatasource $dataSource */
                        $dataSource = $dataGrid->getDatasource();
                        // collect and set entity IDs
                        $entityIds = $this->getAllEntityIds($dataSource, $scope);
                        $this->storage->setData($entityName, $stateHash, $entityIds, $scope);
                    } else {
                        // set empty array as a sign that data is collected, but pagination itself must be disabled
                        $this->storage->setData($entityName, $stateHash, [], $scope);
                    }
                } finally {
                    $dataGrid->setScope($initialScope);
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
        $entityName = $this->getEntityName($dataSource);
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);

        $queryBuilder = $dataSource->getQueryBuilder();
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults($this->getEntitiesLimit());

        $results = $dataSource->getResults();

        $entityIds = [];
        foreach ($results as $result) {
            $entityIds[] = $result->getValue($entityIdentifier);
        }

        return $entityIds;
    }

    /**
     * @param DatagridInterface $dataGrid
     * @param string $scope
     * @return int
     */
    protected function getTotalCount(DatagridInterface $dataGrid, $scope)
    {
        // Depending on scope entity pagination should apply different permission to datasource, e.g. VIEW or EDIT
        $dataGrid->getConfig()->setDatasourceAclApplyPermission(EntityPaginationManager::getPermission($scope));

        /** @var OrmDatasource $dataSource */
        $dataSource = $dataGrid->getDatasource();
        $dataGrid->getAcceptor()->acceptDatasource($dataSource);

        /**
         * Total is already calculated by OrmPagerExtension::visitDatasource when acceptDatasource() was called.
         * Call acceptResult() on fake data to get the value of total.
         *
         * @see \Oro\Bundle\DataGridBundle\Extension\Pager\OrmPagerExtension::visitResult
         */
        $result = ResultsObject::create(['data' => []]);
        $dataGrid->getAcceptor()->acceptResult($result);

        return $result->getTotalRecords();
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
        $parameters = $dataGrid->getParameters()->all();
        if (isset($parameters[PagerInterface::PAGER_ROOT_PARAM])) {
            unset($parameters[PagerInterface::PAGER_ROOT_PARAM]);
        }
        return md5(json_encode($parameters));
    }

    /**
     * @return int
     */
    protected function getEntitiesLimit()
    {
        return $this->paginationManager->getLimit();
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return DatagridInterface|null
     */
    protected function getGrid($name, array $parameters)
    {
        /** @var Manager $manager */
        $manager = $this->datagridManagerLink->getService();

        $dataGrid = null;
        try {
            // datagrid manager automatically extracts all required parameters from request
            $dataGrid = $manager->getDatagridByRequestParams($name, $parameters);
        } catch (\RuntimeException $e) {
            // processing of invalid grid names
        }

        return $dataGrid;
    }

    /**
     * Returns grid names from request
     *
     * @param Request $request
     * @return array
     */
    protected function getGridNames(Request $request)
    {
        $gridNames = array();

        if ($request->query->get('grid')) {
            $gridNames = (array)$request->query->get('grid', []);
            $gridNames = array_keys($gridNames);
        }
        return $gridNames;
    }

    /**
     * Returns entity name or alias from datagrid config
     *
     * @param DatagridInterface $dataGrid
     * @return string
     */
    protected function getEntityNameFromConfig(DatagridInterface $dataGrid)
    {
        $config = $dataGrid->getConfig();
        $alias = null;

        if ($config) {
            $alias = $config->offsetGetByPath(EntityPaginationExtension::ENTITY_PAGINATION_TARGET_PATH);
        }

        return ($alias !== null) ? $alias : $this->getEntityName($dataGrid->getDatasource());
    }
}
