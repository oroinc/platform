<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderAwareInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Event listener applied to all grids with "rowSelection" option. If this option is specified this listener will add:
 *  - js module "orodatagrid/js/datagrid/listener/row-selection-listener" to handle selection behavior on frontend.
 *  - bind query parameters populated by frontend: 'data_in' and 'data_not_in'
 *
 * Datasource query should implement filtering of data based on selection data. Example of grid configuration:
 *
 * datagrids:
 *   bu-update-users-grid:
 *     extends: user-relation-grid
 *     source:
 *       acl_resource: oro_business_unit_update
 *         query:
 *           select:
 *             - >
 *               (CASE WHEN (:business_unit_id IS NOT NULL) THEN
 *                 CASE WHEN (:business_unit_id MEMBER OF u.businessUnits OR u.id IN (:data_in))
 *                   AND u.id NOT IN (:data_not_in)
 *                 THEN true ELSE false END
 *               ELSE
 *                 CASE WHEN u.id IN (:data_in) AND u.id NOT IN (:data_not_in)
 *                 THEN true ELSE false END
 *               END) as hasCurrentBusinessUnit
 *       bind_parameters:
 *         business_unit_id: business_unit_id
 *     columns:
 *       username:
 *         label: oro.user.username.label
 *     options:
 *       rowSelection:
 *         dataField: id
 *         columnName: hasCurrentBusinessUnit
 *         columnLabel: Has business unit
 *         selectors:
 *           included: '#businessUnitAppendUsers'
 *           excluded: '#businessUnitRemoveUsers'
 */
class RowSelectionListener
{
    const ROW_SELECTION_OPTION_PATH             = '[options][rowSelection]';
    const REQUIREJS_MODULES_MODULES_OPTION_PATH = '[options][requireJSModules]';
    const ROW_SELECTION_JS_MODULE               = 'orodatagrid/js/datagrid/listener/column-form-listener';

    /**
     * Included/excluded param names populated by orodatagrid/js/datagrid/listener/column-form-listener on frontend
     */
    const GRID_PARAM_DATA_IN     = 'data_in';
    const GRID_PARAM_DATA_NOT_IN = 'data_not_in';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if (!$datasource instanceof ParameterBinderAwareInterface) {
            return;
        }

        $config = $datagrid->getConfig();

        $rowSelectionConfig = $config->offsetGetByPath(self::ROW_SELECTION_OPTION_PATH, []);

        if (!is_array($rowSelectionConfig) || empty($rowSelectionConfig['columnName'])) {
            return;
        }

        // Add frontend module to handle selection
        $requireJsModules = $config->offsetGetByPath(self::REQUIREJS_MODULES_MODULES_OPTION_PATH, []);

        if (!$requireJsModules || !is_array($requireJsModules)) {
            $requireJsModules = [];
        }

        if (!in_array(self::ROW_SELECTION_JS_MODULE, $requireJsModules)) {
            $requireJsModules[] = self::ROW_SELECTION_JS_MODULE;
        }

        $config->offsetSetByPath(self::REQUIREJS_MODULES_MODULES_OPTION_PATH, $requireJsModules);
        $defaultParameter = $this->getDefaultParameter($config);

        // bind parameters for selection
        $datasource->bindParameters(
            [
                'data_in' => [
                    'name' => self::GRID_PARAM_DATA_IN,
                    'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.' . self::GRID_PARAM_DATA_IN,
                    'default' => $defaultParameter
                ],
                'data_not_in' => [
                    'name' => self::GRID_PARAM_DATA_NOT_IN,
                    'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.' . self::GRID_PARAM_DATA_NOT_IN,
                    'default' => $defaultParameter
                ],
            ]
        );
    }

    /**
     * Default parameter's type must match, so if identity field has `string` type - it has to be `string` either.
     * Otherwise integer is used.
     *
     * @param DatagridConfiguration $config
     * @return array
     */
    private function getDefaultParameter(DatagridConfiguration $config)
    {
        $tableFrom = $config->getOrmQuery()->getFrom();

        // backward compatibility with previous version
        if (!$tableFrom) {
            return [0];
        }

        $targetEntityClass = reset($tableFrom)['table'];

        $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetEntityClass);
        $hasStringFieldType = false;

        foreach ($classMetadata->getIdentifier() as $identifier) {
            if ($classMetadata->getTypeOfField($identifier) === 'string') {
                $hasStringFieldType = true;
                break;
            }
        }

        return $hasStringFieldType ? [''] : [0];
    }
}
