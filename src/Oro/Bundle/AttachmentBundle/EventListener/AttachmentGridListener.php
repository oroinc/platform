<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AttachmentGridListener
{
    const GRID_PARAM_FIELD_NAME = 'entityField';

    const GRID_LEFT_JOIN_PATH = '[source][query][join][left]';

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
        $config    = $event->getConfig();
        $fieldName = $event->getDatagrid()->getParameters()->get(self::GRID_PARAM_FIELD_NAME);

        $leftJoins   = $config->offsetGetByPath(self::GRID_LEFT_JOIN_PATH, []);
        $leftJoins[] = ['join' => 'attachment.' . $fieldName, 'alias' => 'entity'];
        $config->offsetSetByPath(self::GRID_LEFT_JOIN_PATH, $leftJoins);
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
