<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;

/**
 * Handles segment condition builder options to include activity-related criteria.
 *
 * This listener extends the available condition options for segment builders by adding
 * activity-related filtering criteria. It allows users to create segments based on
 * activity conditions, enabling more sophisticated targeting and filtering of entities
 * based on their associated activities.
 */
class SegmentConditionBuilderOptionsListener
{
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
