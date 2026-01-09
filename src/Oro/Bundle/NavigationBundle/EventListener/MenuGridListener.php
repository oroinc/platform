<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\PreBuild;

/**
 * Configures datagrid properties for menu update grids.
 *
 * This listener handles the pre-build event of datagrids to dynamically set view link routes
 * and parameters based on the datagrid configuration. It enables flexible routing configuration
 * for menu management interfaces without hardcoding route information in the datagrid definition.
 */
class MenuGridListener
{
    public const PATH_VIEW_LINK_ROUTE = '[properties][view_link][route]';
    public const PATH_VIEW_LINK_ID = '[properties][view_link][direct_params]';

    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $config->offsetSetByPath(self::PATH_VIEW_LINK_ROUTE, $event->getParameters()->get('viewLinkRoute'));
        $config->offsetSetByPath(self::PATH_VIEW_LINK_ID, $event->getParameters()->get('viewLinkParams'));
    }
}
