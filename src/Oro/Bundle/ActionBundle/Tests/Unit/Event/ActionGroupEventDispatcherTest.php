<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Event;

use Oro\Bundle\ActionBundle\Event\ActionGroupEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupEventDispatcher;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ActionGroupEventDispatcherTest extends TestCase
{
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private ActionGroupEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dispatcher = new ActionGroupEventDispatcher($this->eventDispatcher);
    }

    public function testDispatch(): void
    {
        $definition = new ActionGroupDefinition();
        $definition->setName('test_action_group');

        $data = new ActionData();
        $event = $this->getMockForAbstractClass(ActionGroupEvent::class, [$data, $definition]);
        $event->expects($this->any())
            ->method('getName')
            ->willReturn('test_event');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, 'oro_action_group.test_event'],
                [$event, 'oro_action_group.test_action_group.test_event']
            );

        $this->dispatcher->dispatch($event, 'test_event');
    }
}
