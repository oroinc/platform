<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Datagrid listener for adding join by dynamic field name
 */
class AttachmentGridListener
{
    const GRID_PARAM_FIELD_NAME = 'entityField';
    const GRID_PARAM_TABLE_NAME = 'entityTable';

    /** @var array */
    protected $paramsToBind = [];

    /**
     * @param array $paramsToBind
     */
    public function __construct($paramsToBind = [])
    {
        $this->paramsToBind = $paramsToBind;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $parameters = $event->getDatagrid()->getParameters();

        $tableName = $parameters->get(self::GRID_PARAM_TABLE_NAME);
        $fieldName = $parameters->get(self::GRID_PARAM_FIELD_NAME);

        if ($tableName) {
            $fieldName = ExtendHelper::buildToManyRelationTargetFieldName($tableName, $fieldName);
        }

        $event->getConfig()->getOrmQuery()->addLeftJoin(
            sprintf('%s.%s', $event->getConfig()->getOrmQuery()->getRootAlias(), $fieldName),
            'entity'
        );
    }

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
