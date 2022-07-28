<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\BindParametersInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Exception\LogicException;

/**
 * Event listener applied to all grids with "cellSelection" option. If this option is specified this listener will add:
 *  - js module "orodatagrid/js/datagrid/listener/change-editable-cell-listener" to handle changes behavior on frontend.
 *
 * Example of grid configuration:
 *
 * datagrids:
 *   bu-update-users-grid:
 *       extends: user-relation-grid
 *       source:
 *           acl_resource: oro_business_unit_update
 *           query:
 *               select:
 *                   - CASE WHEN u.enabled = true THEN 'enabled' ELSE 'disabled' END as enabled
 *       columns:
 *           username:
 *               label:         oro.user.username.label
 *           enabled:
 *               label:         oro.user.enabled.label
 *               frontend_type: select
 *               editable:      true
 *               choices:
 *                   enabled: Active
 *                   disabled: Inactive
 *       options:
 *           cellSelection:
 *               dataField: id
 *               columnName:
 *                   - enabled
 *               selector: '#changeset'
 */
class CellSelectionListener
{
    const CELL_SELECTION_OPTION_PATH = '[options][cellSelection]';
    const REQUIRED_MODULES_KEY       = '[options][jsmodules]';
    const CELL_SELECTION_JS_MODULE   = 'orodatagrid/js/datagrid/listener/change-editable-cell-listener';

    /**
     * Required options for selectCell js module
     * @var array
     */
    protected $requiredOptions = [
        'dataField',
        'columnName',
        'selector'
    ];

    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if (!$datasource instanceof BindParametersInterface) {
            return;
        }

        $config = $datagrid->getConfig();

        $selectCellConfig = $config->offsetGetByPath(self::CELL_SELECTION_OPTION_PATH, []);

        if (!is_array($selectCellConfig) || empty($selectCellConfig)) {
            return;
        }

        $missingOptions = array_diff($this->requiredOptions, array_keys($selectCellConfig));

        if ($missingOptions) {
            throw new LogicException(
                sprintf('cellSelection options `%s` are required ', implode('`, `', $missingOptions))
            );
        }

        // Add frontend module to handle selection
        $jsModules = $config->offsetGetByPath(self::REQUIRED_MODULES_KEY, []);

        if (!$jsModules || !is_array($jsModules)) {
            $jsModules = [];
        }

        if (!in_array(self::CELL_SELECTION_JS_MODULE, $jsModules)) {
            $jsModules[] = self::CELL_SELECTION_JS_MODULE;
        }

        $config->offsetSetByPath(self::REQUIRED_MODULES_KEY, $jsModules);
    }
}
