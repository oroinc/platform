<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActivityListBundle\EventListener\SegmentConditionBuilderOptionsListener;
use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;

class SegmentConditionBuilderOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testListener()
    {
        $options = [
            'criteriaListSelector' => '#selector',
            'entityChoiceSelector' => '#selector2',
            'onFieldsUpdate' => [
                'toggleCriteria' => [
                    'condition-item',
                    'condition-segment',
                    'conditions-group',
                ],
            ],
        ];

        $expectedOptions = [
            'criteriaListSelector' => '#selector',
            'entityChoiceSelector' => '#selector2',
            'onFieldsUpdate' => [
                'toggleCriteria' => [
                    'condition-item',
                    'condition-segment',
                    'conditions-group',
                    'condition-activity',
                ],
            ],
        ];

        $listener = new SegmentConditionBuilderOptionsListener();
        $event = new ConditionBuilderOptionsLoadEvent($options);
        $listener->onLoad($event);
        $this->assertEquals($expectedOptions, $event->getOptions());
    }
}
