<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;

class SegmentConditionBuilderOptionsListener
{
    /**
     * @param ConditionBuilderOptionsLoadEvent $event
     */
    public function onLoad(ConditionBuilderOptionsLoadEvent $event)
    {
        $event->setOptions(array_merge_recursive(
            $event->getOptions(),
            [
                'fieldsRelatedCriteria' => [
                    'condition-activity',
                ],
            ]
        ));
    }
}
