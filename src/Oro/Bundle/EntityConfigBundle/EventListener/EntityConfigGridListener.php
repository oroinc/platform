<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class EntityConfigGridListener extends AbstractConfigGridListener
{
    const GRID_NAME = 'entityconfig-grid';

    /**
     * @var array|null
     */
    protected $moduleChoices;

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
        if (null === $this->moduleChoices) {
            $queryBuilder = $this->configManager->getEntityManager()
                ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue')
                ->createQueryBuilder('indexValue')
                ->select('indexValue.value')
                ->distinct()
                ->where('indexValue.scope = :scope')->setParameter('scope', 'entity_config')
                ->andWhere('indexValue.code = :code')->setParameter('code', 'module_name')
                ->orderBy('indexValue.value');

            $result = $queryBuilder->getQuery()->getArrayResult();

            $this->moduleChoices = array();
            foreach ($result as $row) {
                $module = $row['value'];
                $this->moduleChoices[$module] = $module;
            }
        }

        return $this->moduleChoices;
    }
}
