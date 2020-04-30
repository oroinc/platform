<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Event\NotificationEventDispatcher;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager;
use Oro\Component\DependencyInjection\ServiceLink;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationEventDispatcherTest extends TestCase
{
    private const NO_VALUE = 'NO_VALUE';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private $eventDispatcherMock;

    /**
     * @var ServiceLink
     */
    private $notificationManagerMock;

    /**
     * @var NotificationEventDispatcher
     */
    private $notificationEventDispatcher;

    protected function setUp()
    {
        $this->eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
            ->setMethods(
                [
                    'dispatch',
                    'addListener',
                    'addSubscriber',
                    'removeListener',
                    'removeSubscriber',
                    'getListeners',
                    'getListenerPriority',
                    'hasListeners',
                    'customMethod',
                ]
            )
            ->getMock();
        $container = new Container();
        $this->notificationManagerMock = $this->createMock(NotificationManager::class);
        $container->set('oro_notification.manager', $this->notificationManagerMock);
        $serviceLink = new ServiceLink($container, 'oro_notification.manager');
        $this->notificationEventDispatcher = new NotificationEventDispatcher(
            $this->eventDispatcherMock,
            $serviceLink
        );
    }

    public function testDispatchNotNotificationEvent()
    {
        $this->eventDispatcherMock->expects($this->never())
            ->method('addListener');
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'dispatch',
            ['eventName', new Event()]
        );
    }

    public function testDispatchNotificationEvent()
    {
        $this->eventDispatcherMock->expects($this->once())
            ->method('addListener')
            ->with(
                'eventName',
                [
                    $this->notificationManagerMock,
                    'process',
                ],
                0
            );
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'dispatch',
            ['eventName', new NotificationEvent(new \stdClass())]
        );
    }

    /**
     * When the same event dispatched multiple times, the listener should be registered only once
     */
    public function testDispatchNotificationEventMultipleTimes()
    {
        $eventName = 'eventName';
        $notificationEvent = new NotificationEvent(new \stdClass());

        $this->eventDispatcherMock->expects($this->once())
            ->method('addListener')
            ->with(
                $eventName,
                [
                    $this->notificationManagerMock,
                    'process',
                ],
                0
            );

        $this->eventDispatcherMock->expects($this->exactly(3))
            ->method('dispatch')
            ->with($eventName, $notificationEvent);

        $this->notificationEventDispatcher->dispatch($eventName, $notificationEvent);
        $this->notificationEventDispatcher->dispatch($eventName, $notificationEvent);
        $this->notificationEventDispatcher->dispatch($eventName, $notificationEvent);
    }

    public function testDispatchRegisteredNotificationEvent()
    {
        $this->eventDispatcherMock->expects($this->never())
            ->method('addListener');
        $this->notificationEventDispatcher->setRegisteredNotificationEvents(['registered.event']);
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'dispatch',
            ['registered.event', new NotificationEvent(new \stdClass())]
        );
    }

    public function testAddListener()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher('addListener', ['eventName', ['\Class', 'method'], 15]);
    }

    public function testAddSubscriber()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'addSubscriber',
            [$this->createMock(EventSubscriberInterface::class)]
        );
    }

    public function testRemoveListener()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher('removeListener', ['eventName', ['\Class', 'method']]);
    }

    public function testRemoveSubscriber()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'removeSubscriber',
            [$this->createMock(EventSubscriberInterface::class)]
        );
    }

    public function testGetListeners()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'getListeners',
            ['eventName'],
            [1, 2, 3]
        );
    }

    public function testGetListenerPriority()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'getListenerPriority',
            ['eventName', ['\Class', 'method']],
            3
        );
    }

    public function testHasListeners()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'hasListeners',
            ['eventName'],
            true
        );
    }

    public function testCustomMethodCall()
    {
        $this->expectsMethodCallOnDecoratedEventDispatcher(
            'customMethod',
            [1, 2, 3],
            'some_value'
        );
    }

    /**
     * @param string $methodName
     * @param array  $arguments
     * @param mixed  $returnValue
     */
    protected function expectsMethodCallOnDecoratedEventDispatcher(
        string $methodName,
        array $arguments,
        $returnValue = self::NO_VALUE
    ): void {
        $mock = $this->eventDispatcherMock->expects($this->once())
            ->method($methodName)
            ->with(...$arguments);
        if ($returnValue !== self::NO_VALUE) {
            $mock->willReturn($returnValue);
        }
        call_user_func_array([$this->notificationEventDispatcher, $methodName], $arguments);
    }
}
