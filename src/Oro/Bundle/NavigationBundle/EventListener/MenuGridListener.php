<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\PreBuild;

class MenuGridListener
{
    const PATH_VIEW_LINK_ROUTE = '[properties][view_link][route]';
    const PATH_VIEW_LINK_ID = '[properties][view_link][direct_params]';

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $config->offsetSetByPath(self::PATH_VIEW_LINK_ROUTE, $event->getParameters()->get('viewLinkRoute'));
        $config->offsetSetByPath(self::PATH_VIEW_LINK_ID, $event->getParameters()->get('viewLinkParams'));
    }
}
