<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class EntityConfigGridListener extends AbstractConfigGridListener
{
    const GRID_NAME = 'entityconfig-grid';


    /** @var ConfigManager */
    protected $configManager;

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
    public function getModuleChoices()
    {
        $qb = $this->configManager->getEntityManager()->createQueryBuilder();
        $qb->select('entity.moduleName')
            ->distinct()
            ->from(EntityConfigModel::ENTITY_NAME, 'entity')
            ->orderBy('entity.moduleName');
        $result = $qb->getQuery()->getArrayResult();

        $modules = array();
        foreach ($result as $row) {
            $module = $row['moduleName'];
            $modules[$module] = $module;
        }

        return $modules;
    }
}
