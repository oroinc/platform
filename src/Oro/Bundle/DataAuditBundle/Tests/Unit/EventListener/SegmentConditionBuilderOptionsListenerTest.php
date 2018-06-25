<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\EventListener\SegmentConditionBuilderOptionsListener;
use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;

class SegmentConditionBuilderOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testListener()
    {
        $options = [
            'fieldsRelatedCriteria' => [
                'condition-item',
                'condition-segment',
            ],
        ];

        $expectedOptions = [
            'fieldsRelatedCriteria' => [
                'condition-item',
                'condition-segment',
                'condition-data-audit',
            ],
        ];

        $listener = new SegmentConditionBuilderOptionsListener();
        $event = new ConditionBuilderOptionsLoadEvent($options);
        $listener->onLoad($event);
        $this->assertEquals($expectedOptions, $event->getOptions());
    }
}
