<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderAwareInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Exception\LogicException;

class SelectCellListener
{
    const SELECT_CELL_OPTION_PATH               = '[options][selectCell]';
    const REQUIREJS_MODULES_MODULES_OPTION_PATH = '[options][requireJSModules]';
    const SELECT_CELL_JS_MODULE                 = 'orodatagrid/js/datagrid/listener/select-cell-form-listener';

    /**
     * Required options for selectCell js module
     * @var array
     */
    protected $requiredOptions = [
        'dataField',
        'fields',
        'changeset'
    ];

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

        $selectCellConfig = $config->offsetGetByPath(self::SELECT_CELL_OPTION_PATH, []);

        if (!is_array($selectCellConfig) || empty($selectCellConfig)) {
            return;
        }

        $missingOptions = array_diff($this->requiredOptions, array_keys($selectCellConfig));

        if ($missingOptions) {
            throw new LogicException(sprintf('SelectCell options %s are required ', implode(', ', $missingOptions)));
        }

        // Add frontend module to handle selection
        $requireJsModules = $config->offsetGetByPath(self::REQUIREJS_MODULES_MODULES_OPTION_PATH, []);

        if (!$requireJsModules || !is_array($requireJsModules)) {
            $requireJsModules = [];
        }

        if (!in_array(self::SELECT_CELL_JS_MODULE, $requireJsModules)) {
            $requireJsModules[] = self::SELECT_CELL_JS_MODULE;
        }

        $config->offsetSetByPath(self::REQUIREJS_MODULES_MODULES_OPTION_PATH, $requireJsModules);
    }
}
