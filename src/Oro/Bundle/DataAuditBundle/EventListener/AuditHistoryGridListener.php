<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AuditHistoryGridListener
{
    const GRID_PARAM_CLASS      = 'object_class';
    const GRID_PARAM_OBJECT_ID  = 'object_id';
    const GRID_PARAM_FIELD_NAME = 'field_name';

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
     * Used only for auditfield-log-grid grid (subscribed in services.yml)
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $fieldName = $event->getDatagrid()->getParameters()->get(self::GRID_PARAM_FIELD_NAME, false);
        $config->offsetSetByPath('[columns][diffs][context][field_name]', $fieldName);
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters = $datagrid->getParameters();
            $queryBuilder = $datasource->getQueryBuilder();

            $queryParameters = array(
                'objectClass' => str_replace('_', '\\', $parameters->get(self::GRID_PARAM_CLASS, '')),
            );

            if (in_array('objectId', $this->paramsToBind)) {
                $queryParameters['objectId'] = $parameters->get(self::GRID_PARAM_OBJECT_ID, 0);
            }

            $fieldName = $parameters->get(self::GRID_PARAM_FIELD_NAME, false);
            if (!empty($fieldName) && in_array('fieldName', $this->paramsToBind)) {
                $queryParameters['fieldName'] = $fieldName;
            }

            $queryBuilder->setParameters($queryParameters);
        }
    }
}
