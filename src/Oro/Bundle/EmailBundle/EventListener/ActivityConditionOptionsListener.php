<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ActivityListBundle\Event\ActivityConditionOptionsLoadEvent;

class ActivityConditionOptionsListener
{
    /**
     * @param ActivityConditionOptionsLoadEvent $event
     */
    public function onLoad(ActivityConditionOptionsLoadEvent $event)
    {
        $event->setOptions(array_merge_recursive(
            $event->getOptions(),
            [
                'extensions' => [
                    'oroemail/js/activity-condition-extension',
                ],
            ]
        ));
    }
}
