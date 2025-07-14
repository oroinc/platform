<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Event;

use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;
use PHPUnit\Framework\TestCase;

class BeforeGroupingChainWidgetEventTest extends TestCase
{
    public function testEvent(): void
    {
        $pageType = 1;
        $widgets = ['some' => 'data'];
        $object = new \stdClass();

        $event = new BeforeGroupingChainWidgetEvent($pageType, $widgets, $object);

        $this->assertEquals($pageType, $event->getPageType());
        $this->assertEquals($widgets, $event->getWidgets());
        $this->assertEquals($object, $event->getEntity());

        $newWidgets = ['new' => 'widgets'];
        $event->setWidgets($newWidgets);
        $this->assertEquals($newWidgets, $event->getWidgets());
    }
}
