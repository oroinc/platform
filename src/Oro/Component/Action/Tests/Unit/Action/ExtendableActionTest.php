<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExtendableActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|MockObject */
    private $dispatcher;

    /**
     * @var ExtendableAction
     */
    private $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->action = new class(new ContextAccessor()) extends ExtendableAction {
            public function xgetSubscribedEvents(): array
            {
                return $this->subscribedEvents;
            }
        };
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
        static::assertEquals($events, $this->action->xgetSubscribedEvents());
        static::assertInstanceOf(ActionInterface::class, $result);
    }

    public function testExecute()
    {
        $context = new ItemStub();
        $eventWithoutListeners = 'some_event_without_listeners';
        $eventWithListeners = 'some_event_with_listeners';
        $event = new ExtendableActionEvent($context);

        $this->dispatcher->expects(static::exactly(2))
            ->method('hasListeners')
            ->withConsecutive(
                [$eventWithoutListeners],
                [$eventWithListeners]
            )
            ->willReturn(false, true);
        $this->dispatcher->expects(static::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [static::anything(), ExecuteActionEvents::HANDLE_BEFORE],
                [$event, $eventWithListeners],
                [static::anything(), ExecuteActionEvents::HANDLE_AFTER]
            );

        $this->action->initialize(['events' => [$eventWithoutListeners, $eventWithListeners]]);
        $this->action->execute($context);
    }
}
