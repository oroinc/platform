<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Twig;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Twig\SegmentExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SegmentExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private EventDispatcherInterface $dispatcher;

    private SegmentExtension $segmentExtension;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $container = self::getContainerBuilder()
            ->add(EventDispatcherInterface::class, $this->dispatcher)
            ->getContainer($this);

        $this->segmentExtension = new SegmentExtension($container);
    }

    public function testUpdateSegmentWidgetOptionsShouldReturnOriginalOptionsIfThereAreNoListeners(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(WidgetOptionsLoadEvent::EVENT_NAME)
            ->willReturn(false);

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentWidgetOptions($originalWidgetOptions);
        $this->assertEquals($originalWidgetOptions, $options);
    }

    public function testUpdateSegmentConditionBuilderOptionsShouldReturnOriginalOptionsIfThereAreNoListeners(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(ConditionBuilderOptionsLoadEvent::EVENT_NAME)
            ->willReturn(false);

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentConditionBuilderOptions($originalWidgetOptions);
        $this->assertEquals($originalWidgetOptions, $options);
    }

    public function testUpdateSegmentWidgetOptionsShouldReturnOptionsFromListener(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(WidgetOptionsLoadEvent::EVENT_NAME)
            ->willReturn(true);

        $eventOptions = ['eventOpt1' => 'eventVal1'];
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(static::anything(), WidgetOptionsLoadEvent::EVENT_NAME)
            ->willReturnCallback(function ($event) use ($eventOptions) {
                $event->setWidgetOptions($eventOptions);

                return $event;
            });

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentWidgetOptions($originalWidgetOptions);
        $this->assertEquals($eventOptions, $options);
    }

    public function testUpdateSegmentConditionBuilderOptionsShouldReturnOptionsFromListener(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(ConditionBuilderOptionsLoadEvent::EVENT_NAME)
            ->willReturn(true);

        $eventOptions = ['eventOpt1' => 'eventVal1'];
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(static::anything(), ConditionBuilderOptionsLoadEvent::EVENT_NAME)
            ->willReturnCallback(function ($event, $eventName) use ($eventOptions) {
                $event->setOptions($eventOptions);

                return $event;
            });

        $originalWidgetOptions = ['opt1' => 'val1'];
        $options = $this->segmentExtension->updateSegmentConditionBuilderOptions($originalWidgetOptions);
        $this->assertEquals($eventOptions, $options);
    }
}
