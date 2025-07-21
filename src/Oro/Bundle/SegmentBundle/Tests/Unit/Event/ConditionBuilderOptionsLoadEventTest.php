<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Event;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;
use PHPUnit\Framework\TestCase;

class ConditionBuilderOptionsLoadEventTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $originalOptions = [
            'oo' => 'ov',
        ];

        $options = [
            'option1' => 'val',
        ];

        $event = new ConditionBuilderOptionsLoadEvent($originalOptions);
        $this->assertEquals($originalOptions, $event->getOptions());

        $event->setOptions($options);
        $this->assertEquals($options, $event->getOptions());
    }
}
