<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

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
    public function testInitializeWhenThrowsException(
        array $options,
        string $expectedException,
        string $exceptionMessage
    ) {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeWhenThrowsExceptionProvider(): array
    {
        return [
            'no required options' => [
                'options' => [],
                'expectedException' => MissingOptionsException::class,
                'exceptionMessage' => 'The required option "events" is missing.',
            ],
            'wrong events option type' => [
                'options' => ['events' => 'wrongEventsOptionType'],
                'expectedException' => InvalidOptionsException::class,
                'exceptionMessage' => 'The option "events" with value "wrongEventsOptionType" is expected to be of '
                    . 'type "array" or "' . PropertyPathInterface::class . '", but is of type "string".'
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
        $context = new ExtendableEventData([]);
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

    public function testExecuteWithPassedEventData()
    {
        $context = new ExtendableEventData([]);
        $eventWithoutListeners = 'some_event_without_listeners';
        $eventWithListeners = 'some_event_with_listeners';
        $data = ['key' => 'value'];
        $event = new ExtendableActionEvent(new ExtendableEventData(['key' => 'value']));

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

        $this->action->initialize([
            'events' => [$eventWithoutListeners, $eventWithListeners],
            'eventData' => $data
        ]);
        $this->action->execute($context);
    }

    public function testExecuteWithActionDataStorageAwareInterface()
    {
        $context = $this->createMock(ActionDataStorageAwareInterface::class);
        $dataStorage = $this->createMock(AbstractStorage::class);
        $context->expects($this->once())
            ->method('getActionDataStorage')
            ->willReturn($dataStorage);
        $eventWithoutListeners = 'some_event_without_listeners';
        $eventWithListeners = 'some_event_with_listeners';
        $event = new ExtendableActionEvent($dataStorage);

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

        $this->action->initialize([
            'events' => [$eventWithoutListeners, $eventWithListeners]
        ]);
        $this->action->execute($context);
    }

    public function testExecuteWithArray()
    {
        $context = ['key' => 'value'];
        $eventWithoutListeners = 'some_event_without_listeners';
        $eventWithListeners = 'some_event_with_listeners';
        $event = new ExtendableActionEvent(new ExtendableEventData($context));

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

        $this->action->initialize([
            'events' => [$eventWithoutListeners, $eventWithListeners]
        ]);
        $this->action->execute($context);
    }
}
