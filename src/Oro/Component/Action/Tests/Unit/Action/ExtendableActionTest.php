<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExtendableActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var ExtendableAction */
    private $action;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new ExtendableAction(new ContextAccessor());
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @dataProvider initializeWhenThrowsExceptionProvider
     */
    public function testInitializeWhenThrowsException(array $options, string $exceptionMessage)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeWhenThrowsExceptionProvider(): array
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
        self::assertEquals($events, ReflectionUtil::getPropertyValue($this->action, 'subscribedEvents'));
        self::assertInstanceOf(ActionInterface::class, $result);
    }

    public function testExecute()
    {
        $context = new ItemStub();
        $eventWithoutListeners = 'some_event_without_listeners';
        $eventWithListeners = 'some_event_with_listeners';
        $event = new ExtendableActionEvent($context);

        $this->dispatcher->expects(self::exactly(2))
            ->method('hasListeners')
            ->withConsecutive(
                [$eventWithoutListeners],
                [$eventWithListeners]
            )
            ->willReturn(false, true);
        $this->dispatcher->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [self::anything(), ExecuteActionEvents::HANDLE_BEFORE],
                [$event, $eventWithListeners],
                [self::anything(), ExecuteActionEvents::HANDLE_AFTER]
            );

        $this->action->initialize(['events' => [$eventWithoutListeners, $eventWithListeners]]);
        $this->action->execute($context);
    }
}
