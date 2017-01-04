<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AttachmentGridListener
{
    const GRID_PARAM_FIELD_NAME = 'entityField';

    /** @var array */
    protected $paramsToBind = [];

    /**
     * @param array $paramsToBind
     */
    public function __construct($paramsToBind = [])
    {
        $this->paramsToBind = $paramsToBind;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $fieldName = $event->getDatagrid()->getParameters()->get(self::GRID_PARAM_FIELD_NAME);
        $event->getConfig()->getOrmQuery()->addLeftJoin('attachment.' . $fieldName, 'entity');
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $dataSource = $event->getDatagrid()->getDatasource();

        if ($dataSource instanceof OrmDatasource) {
            $parameters   = $datagrid->getParameters();
            $queryBuilder = $dataSource->getQueryBuilder();
            $params       = [];

            foreach ($this->paramsToBind as $fieldName) {
                $param              = $parameters->get($fieldName, null);
                $params[$fieldName] = $param;
            }

            $queryBuilder->setParameters($params);
        }
    }
}
