<?php

namespace Oro\Bundle\UI\Tests\Unit\Event;

use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;

class BeforeGroupingChainWidgetEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
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
