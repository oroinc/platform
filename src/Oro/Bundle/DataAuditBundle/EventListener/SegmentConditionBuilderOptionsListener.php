<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;

/**
 * Handles {@see ConditionBuilderOptionsLoadEvent} to register data audit filter criteria.
 *
 * This listener extends the segment condition builder by adding the `condition-data-audit` filter type,
 * enabling segments to filter records based on audit history (e.g., field changes to specific values
 * within a time period). This integration allows users to create dynamic segments based on entity
 * change history tracked by the data audit system.
 */
class SegmentConditionBuilderOptionsListener
{
    public function onLoad(ConditionBuilderOptionsLoadEvent $event)
    {
        $event->setOptions(array_merge_recursive(
            $event->getOptions(),
            [
                'fieldsRelatedCriteria' => [
                    'condition-data-audit',
                ],
            ]
        ));
    }
}
