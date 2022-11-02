<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler\Decorator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelActionHandlerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\Decorator\ChannelActionHandlerDispatcherDecorator;
use Oro\Bundle\IntegrationBundle\ActionHandler\Error\ChannelActionErrorHandlerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelActionEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelActionEventFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChannelActionHandlerDispatcherDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var ChannelActionEventFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventFactory;

    /** @var ChannelActionHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $actionHandler;

    /** @var ChannelActionErrorHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $errorHandler;

    /** @var ChannelActionHandlerDispatcherDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventFactory = $this->createMock(ChannelActionEventFactoryInterface::class);
        $this->actionHandler = $this->createMock(ChannelActionHandlerInterface::class);
        $this->errorHandler = $this->createMock(ChannelActionErrorHandlerInterface::class);

        $this->decorator = new ChannelActionHandlerDispatcherDecorator(
            $this->dispatcher,
            $this->eventFactory,
            $this->actionHandler,
            $this->errorHandler
        );
    }

    public function testHandleActionWithErrors()
    {
        $channel = new Channel();
        $errors = new ArrayCollection(['error1']);

        $event = $this->createMock(ChannelActionEvent::class);
        $event->expects(self::any())
            ->method('getName')
            ->willReturn('test_event');
        $event->expects(self::any())
            ->method('getErrors')
            ->willReturn($errors);

        $this->eventFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($channel))
            ->willReturn($event);

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::identicalTo($event), $event->getName());

        $this->actionHandler->expects(self::never())
            ->method('handleAction');
        $this->errorHandler->expects(self::once())
            ->method('handleErrors')
            ->with(self::identicalTo($errors));

        self::assertFalse($this->decorator->handleAction($channel));
    }

    public function testHandleActionWithNoErrors()
    {
        $channel = new Channel();

        $event = $this->createMock(ChannelActionEvent::class);
        $event->expects(self::any())
            ->method('getName')
            ->willReturn('test_event');
        $event->expects(self::any())
            ->method('getErrors')
            ->willReturn(new ArrayCollection());

        $this->eventFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($channel))
            ->willReturn($event);

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::identicalTo($event), $event->getName());

        $this->actionHandler->expects(self::once())
            ->method('handleAction')
            ->with(self::identicalTo($channel));
        $this->errorHandler->expects(self::never())
            ->method('handleErrors');

        self::assertTrue($this->decorator->handleAction($channel));
    }
}
