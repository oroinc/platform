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
        $config = $event->getConfig();
        $datagrid =  $event->getDatagrid();
        $fieldName = $datagrid->getParameters()->get(self::GRID_PARAM_FIELD_NAME);

        $leftJoins = $config->offsetGetByPath('[source][query][join][left]', []);
        $leftJoins[] =['join'  => 'attachment.' . $fieldName, 'alias' => 'e'];
        $config->offsetSetByPath('[source][query][join][left]', $leftJoins);
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $dataSource = $event->getDatagrid()->getDatasource();

        if ($dataSource instanceof OrmDatasource) {
            $parameters = $datagrid->getParameters();
            $queryBuilder = $dataSource->getQueryBuilder();
            $params = array();

            foreach ($this->paramsToBind as $fieldName) {
                $param = $parameters->get($fieldName, null);
                $params[$fieldName] = $param;
            }

            $queryBuilder->setParameters($params);
        }
    }
}
