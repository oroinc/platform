<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Event;

use Oro\Bundle\ActionBundle\Event\OperationEvent;
use Oro\Bundle\ActionBundle\Event\OperationEventDispatcher;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OperationEventDispatcherTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private OperationEventDispatcher $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dispatcher = new OperationEventDispatcher($this->eventDispatcher);
    }

    public function testDispatch(): void
    {
        $definition = new OperationDefinition();
        $definition->setName('test_operation');

        $data = new ActionData();
        $event = $this->getMockForAbstractClass(OperationEvent::class, [$data, $definition]);
        $event->expects($this->any())
            ->method('getName')
            ->willReturn('test_event');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, 'oro_operation.test_event'],
                [$event, 'oro_operation.test_operation.test_event']
            );

        $this->dispatcher->dispatch($event, 'test_event');
    }
}
