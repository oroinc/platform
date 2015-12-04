<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;

class SegmentSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WidgetOptionsLoadEvent::EVENT_NAME => 'loadAggregatedFieldsWidgetOptions',
            ConditionBuilderOptionsLoadEvent::EVENT_NAME => 'loadAggregatedFieldsBuilderOptions',
        ];
    }

    /**
     * @param WidgetOptionsLoadEvent $event
     */
    public function loadAggregatedFieldsWidgetOptions(WidgetOptionsLoadEvent $event)
    {
        if ($event->getWidgetType() !== 'oro_report') {
            return;
        }

        $widgetOptions = $event->getWidgetOptions();
        $fieldsLoader = $widgetOptions['fieldsLoader'];

        $event->setWidgetOptions(array_merge_recursive(
            $widgetOptions,
            [
                'aggregatedFieldsLoader' => [
                    'entityChoice'      => $fieldsLoader['entityChoice'],
                    'loadingMaskParent' => $fieldsLoader['loadingMaskParent'],
                    'router'            => 'oro_api_querydesigner_aggregated_fields',
                    'routingParams'     => [],
                    'fieldsData'        => $fieldsLoader['fieldsData'],
                    'confirmMessage'    => $fieldsLoader['confirmMessage'],
                ],
                'extensions' => [
                    'orosegment/js/app/components/aggregated-field-condition-extension',
                ],
            ]
        ));
    }

    /**
     * @param ConditionBuilderOptionsLoadEvent $event
     */
    public function loadAggregatedFieldsBuilderOptions(ConditionBuilderOptionsLoadEvent $event)
    {
        $event->setOptions(array_merge_recursive(
            $event->getOptions(),
            [
                'onFieldsUpdate' => [
                    'toggleCriteria' => [
                        'aggregated-condition-item',
                    ],
                ],
            ]
        ));
    }
}
