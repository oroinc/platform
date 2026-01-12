<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to segment-related events to extend report functionality with aggregated field support.
 *
 * This subscriber listens to widget options load and condition builder options load events
 * from the SegmentBundle. When a report widget is being configured, it injects JavaScript
 * extensions that enable support for aggregated field conditions in the report builder UI.
 */
class SegmentSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            WidgetOptionsLoadEvent::EVENT_NAME => 'loadAggregatedFieldsWidgetOptions',
            ConditionBuilderOptionsLoadEvent::EVENT_NAME => 'loadAggregatedFieldsBuilderOptions',
        ];
    }

    public function loadAggregatedFieldsWidgetOptions(WidgetOptionsLoadEvent $event)
    {
        if ($event->getWidgetType() !== 'oro_report') {
            return;
        }

        $event->setWidgetOptions(array_merge_recursive(
            $event->getWidgetOptions(),
            [
                'extensions' => [
                    'orosegment/js/app/components/aggregated-field-condition-extension',
                ],
            ]
        ));
    }

    public function loadAggregatedFieldsBuilderOptions(ConditionBuilderOptionsLoadEvent $event)
    {
        $event->setOptions(array_merge_recursive(
            $event->getOptions(),
            [
                'fieldsRelatedCriteria' => [
                    'aggregated-condition-item',
                ],
            ]
        ));
    }
}
