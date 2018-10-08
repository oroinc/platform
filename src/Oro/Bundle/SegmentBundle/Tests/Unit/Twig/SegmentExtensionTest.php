<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Twig;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Twig\SegmentExtension;

class SegmentExtensionTest extends \PHPUnit\Framework\TestCase
{
    protected $dispatcher;

    protected $segmentExtension;

    public function setUp()
    {
        $this->dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->segmentExtension = new SegmentExtension($this->dispatcher);
    }

    public function testUpdateSegmentWidgetOptionsShouldReturnOriginalOptionsIfThereAreNoListeners()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(WidgetOptionsLoadEvent::EVENT_NAME)
            ->will($this->returnValue(false));

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentWidgetOptions($originalWidgetOptions);
        $this->assertEquals($originalWidgetOptions, $options);
    }

    public function testUpdateSegmentConditionBuilderOptionsShouldReturnOriginalOptionsIfThereAreNoListeners()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(ConditionBuilderOptionsLoadEvent::EVENT_NAME)
            ->will($this->returnValue(false));

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentConditionBuilderOptions($originalWidgetOptions);
        $this->assertEquals($originalWidgetOptions, $options);
    }

    public function testUpdateSegmentWidgetOptionsShouldReturnOptionsFromListener()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(WidgetOptionsLoadEvent::EVENT_NAME)
            ->will($this->returnValue(true));

        $eventOptions = ['eventOpt1' => 'eventVal1'];
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(WidgetOptionsLoadEvent::EVENT_NAME)
            ->will($this->returnCallback(function ($eventName, $event) use ($eventOptions) {
                $event->setWidgetOptions($eventOptions);
            }));

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentWidgetOptions($originalWidgetOptions);
        $this->assertEquals($eventOptions, $options);
    }

    public function testUpdateSegmentConditionBuilderOptionsShouldReturnOptionsFromListener()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(ConditionBuilderOptionsLoadEvent::EVENT_NAME)
            ->will($this->returnValue(true));

        $eventOptions = ['eventOpt1' => 'eventVal1'];
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ConditionBuilderOptionsLoadEvent::EVENT_NAME)
            ->will($this->returnCallback(function ($eventName, $event) use ($eventOptions) {
                $event->setOptions($eventOptions);
            }));

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentConditionBuilderOptions($originalWidgetOptions);
        $this->assertEquals($eventOptions, $options);
    }
}
