<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActivityListBundle\Event\ActivityConditionOptionsLoadEvent;
use Oro\Bundle\EmailBundle\EventListener\ActivityConditionOptionsListener;

class ActivityConditionOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnLoad()
    {
        $options = [
            'option' => 'value',
            'extensions' => [
                'ext1',
            ],
        ];

        $expectedOptions = [
            'option' => 'value',
            'extensions' => [
                'ext1',
                'oroemail/js/activity-condition-extension',
            ],
        ];

        $event = new ActivityConditionOptionsLoadEvent($options);
        $listener = new ActivityConditionOptionsListener();
        $listener->onLoad($event);

        $this->assertEquals($expectedOptions, $event->getOptions());
    }
}
