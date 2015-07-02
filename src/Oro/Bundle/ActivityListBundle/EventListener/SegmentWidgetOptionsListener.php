<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;

class SegmentWidgetOptionsListener
{
    /**
     * @param WidgetOptionsLoadEvent $event
     *
     * @return array
     */
    public function onLoad(WidgetOptionsLoadEvent $event)
    {
        $event->setWidgetOptions(array_merge_recursive(
            $event->getWidgetOptions(),
            [
                'extensions' => [
                    'oroactivitylist/js/app/components/segment-component-extension',
                ],
            ]
        ));
    }
}
