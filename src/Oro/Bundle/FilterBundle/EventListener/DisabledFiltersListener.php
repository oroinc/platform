<?php

namespace Oro\Bundle\FilterBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;

class DisabledFiltersListener
{
    const PARAM_NAME = 'datagrid-no-filters';

    /**
     * Checks whether 'datagrid-no-filters' parameters exits and if it's equals true
     * Removes filters node from grid's config.
     * This might be used when should be possible to display same grid with/with out filters in different places.
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $params = $event->getParameters();

        if (isset($params[self::PARAM_NAME]) && $params[self::PARAM_NAME]) {
            $config = $event->getConfig();
            $config->offsetUnsetByPath(Configuration::FILTERS_PATH);
        }
    }
}
