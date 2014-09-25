<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

/**
 * Class BaseRelationDatagridListener
 * @package Oro\Bundle\DataGridBundle\EventListener
 *
 * Event listener should be applied when entities relation managed via datagrid
 */
class BaseOrmRelationDatagridListener
{
    /**
     * Included/excluded param names
     * populated by orodatagrid/js/datagrid/listener/column-form-listener on frontend
     */
    const GRID_PARAM_DATA_IN     = 'data_in';
    const GRID_PARAM_DATA_NOT_IN = 'data_not_in';

    /** @var array */
    protected $parameterNames;

    /** @var boolean */
    protected $isEditMode;

    /**
     * @param array|string $paramName  One or more parameter names that should be taken from request and binded to query
     * @param bool         $isEditMode whether or not to add data_in, data_not_in params to query
     */
    public function __construct($paramName, $isEditMode = true)
    {
        $this->parameterNames = is_array($paramName) ? $paramName : [$paramName];
        $this->isEditMode     = $isEditMode;
    }

    /**
     * Add filters to where clause
     * Base query should looks as following:
     * (CASE WHEN (:relationParamName IS NOT NULL) THEN
     *       CASE WHEN (:relationParamName
     *              MEMBER OF alias.relationField OR alias.id IN (:data_in)) AND alias.id NOT IN (:data_not_in)
     *       THEN true ELSE false END
     *  ELSE
     *       CASE WHEN alias.id IN (:data_in) AND alias.id NOT IN (:data_not_in)
     *       THEN true ELSE false END
     *  END) as relationColumnName
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        $parameters = $datagrid->getParameters();
        if ($datasource instanceof OrmDatasource) {
            /** @var QueryBuilder $query */
            $queryBuilder = $datasource->getQueryBuilder();

            $additionalParams = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

            $dataIn  = [0];
            $dataOut = [0];

            if (isset($additionalParams[self::GRID_PARAM_DATA_IN])) {
                $filteredParams = array_filter($additionalParams[self::GRID_PARAM_DATA_IN]);
                if (!empty($filteredParams)) {
                    $dataIn = $additionalParams[self::GRID_PARAM_DATA_IN];
                }
            }

            if (isset($additionalParams[self::GRID_PARAM_DATA_NOT_IN])) {
                $filteredParams = array_filter($additionalParams[self::GRID_PARAM_DATA_NOT_IN]);
                if (!empty($filteredParams)) {
                    $dataOut = $additionalParams[self::GRID_PARAM_DATA_NOT_IN];
                }
            }

            $queryParameters = [
                'data_in'        => $dataIn,
                'data_not_in'    => $dataOut,
            ];

            foreach ($this->parameterNames as $paramName) {
                $queryParameters[$paramName] = $parameters->get($paramName);
            }

            if (!$this->isEditMode) {
                unset($queryParameters['data_in'], $queryParameters['data_not_in']);
            }

            $queryBuilder->setParameters($queryParameters);
        }
    }
}
