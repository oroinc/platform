<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

class EntityConfigGridListener extends AbstractConfigGridListener
{
    const GRID_NAME = 'entityconfig-grid';


    /** @var ConfigManager */
    protected $configManager;

    /** @var array Filter choices for name and module column filters */
    protected $filterChoices = ['name' => [], 'module' => []];

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $queryBuilder = $datasource->getQueryBuilder();

            $this->prepareQuery($queryBuilder, 'ce', 'cev', PropertyConfigContainer::TYPE_ENTITY);
        }
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $this->doBuildBefore($event, 'cev', PropertyConfigContainer::TYPE_ENTITY);
    }

    /**
     * Call this method from datagrid.yml
     * invoked in Manager when datagrid configuration prepared for grid build process
     *
     * @return array
     */
    public function getChoicesName()
    {
        return $this->getObjectName();
    }

    /**
     * Call this method from datagrid.yml
     * invoked in Manager when datagrid configuration prepared for grid build process
     *
     * @return array
     */
    public function getChoicesModule()
    {
        return $this->getObjectName('module');
    }

    /**
     *
     * @param  string $scope
     * @return array
     */
    protected function getObjectName($scope = 'name')
    {
        if (empty($this->filterChoices[$scope])) {
            $alias = 'ce';
            $qb = $this->configManager->getEntityManager()->createQueryBuilder();
            $qb->select($alias)
                ->from(EntityConfigModel::ENTITY_NAME, $alias)
                ->add('select', $alias . '.className')
                ->distinct($alias.'.className');

            $result = $qb->getQuery()->getArrayResult();

            $options = ['name' => [], 'module' => []];
            foreach ((array) $result as $value) {
                list($moduleName, $entityName) = ConfigHelper::getModuleAndEntityNames($value['className']);
                $options['module'][$value['className']] = $moduleName;
                $options['name'][$value['className']]   = $entityName;
            }

            $this->filterChoices = $options;
        }

        return $this->filterChoices[$scope];
    }
}
