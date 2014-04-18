<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

class DisableToolbarByRecordCountListener
{
    const PARAM_NAME = 'turn-off-toolbar-records-number';

    /**
     * Hide toolbar if count of records < turn-off-toolbar-records-number parameter
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $params = $event->getParameters();

        if (isset($params[self::PARAM_NAME]) && (int)$params[self::PARAM_NAME] > 0) {
            $config = $event->getConfig();
            $config->offsetSetByPath(
                ToolbarExtension::TURN_OFF_TOOLBAR_RECORDS_NUMBER_PATH,
                (int)$params[self::PARAM_NAME]
            );
        }
    }
}
