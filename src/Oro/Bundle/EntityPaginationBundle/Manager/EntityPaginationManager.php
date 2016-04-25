<?php

namespace Oro\Bundle\EntityPaginationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;

class EntityPaginationManager
{
    const VIEW_SCOPE = 'view';
    const EDIT_SCOPE = 'edit';

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->configManager->get('oro_entity_pagination.enabled');
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return (int)$this->configManager->get('oro_entity_pagination.limit');
    }

    /**
     * @param DatagridInterface $dataGrid
     * @return bool
     */
    public function isDatagridApplicable(DatagridInterface $dataGrid)
    {
        if (!$dataGrid->getDatasource() instanceof OrmDatasource) {
            return false;
        }

        return $dataGrid->getConfig()->offsetGetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH) === true;
    }

    /**
     * @param string $scope
     * @return string
     * @throws \LogicException
     */
    public static function getPermission($scope)
    {
        switch ($scope) {
            case self::VIEW_SCOPE:
                return 'VIEW';
            case self::EDIT_SCOPE:
                return 'EDIT';
        }

        throw new \LogicException(sprintf('Scope "%s" is not available.', $scope));
    }
}
