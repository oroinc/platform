<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class MergeMassActionListener
{
    /**
     * Remove useless fields in case of filtering
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $parameters = $event->getParameters();
    }
}
