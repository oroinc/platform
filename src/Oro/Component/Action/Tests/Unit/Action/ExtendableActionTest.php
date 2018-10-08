<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExtendableActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var ExtendableAction
     */
    private $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->action = new ExtendableAction(new ContextAccessor());
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @dataProvider initializeWhenThrowsExceptionProvider
     * @param array $options
     * @param string $exceptionMessage
     */
    public function testInitializeWhenThrowsException(array $options, $exceptionMessage)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeWhenThrowsExceptionProvider()
    {
        return [
            'no required options' => [
                'options' => [],
                'exceptionMessage' => 'The required option "events" is missing.',
            ],
            'wrong events option type' => [
                'options' => ['events' => 'wrongEventsOptionType'],
                'exceptionMessage' => 'The option "events" is expected to be of type "array", "string" given.'
            ],
        ];
    }

    public function testInitialize()
    {
        $events = ['some_event_name'];
        $result = $this->action->initialize(['events' => $events]);
        $this->assertAttributeEquals($events, 'subscribedEvents', $this->action);
        $this->assertInstanceOf(ActionInterface::class, $result);
    }

    public function testExecute()
    {
        $context = new ItemStub();
        $eventWithoutListeners = 'some_event_without_listeners';
        $eventWithListeners = 'some_event_with_listeners';
        $event = new ExtendableActionEvent($context);

        $this->dispatcher->expects($this->exactly(2))
            ->method('hasListeners')
            ->withConsecutive(
                [$eventWithoutListeners],
                [$eventWithListeners]
            )
            ->willReturn(false, true);
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [ExecuteActionEvents::HANDLE_BEFORE, $this->anything()],
                [$eventWithListeners, $event],
                [ExecuteActionEvents::HANDLE_AFTER, $this->anything()]
            );

        $this->action->initialize(['events' => [$eventWithoutListeners, $eventWithListeners]]);
        $this->action->execute($context);
    }
}
