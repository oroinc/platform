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
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var ChannelActionEventFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventFactory;

    /**
     * @var ChannelActionHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $actionHandler;

    /**
     * @var ChannelActionErrorHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $errorHandler;

    /**
     * @var ChannelActionHandlerDispatcherDecorator
     */
    private $decorator;

    protected function setUp()
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->dispatcher->expects(static::once())->method('dispatch');

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
        $event = $this->createMock(ChannelActionEvent::class);
        $event->expects(static::any())
            ->method('getErrors')
            ->willReturn(new ArrayCollection(['error1']));

        $this->eventFactory->expects(static::once())
            ->method('create')
            ->willReturn($event);

        $this->actionHandler->expects(static::never())->method('handleAction');

        static::assertFalse($this->decorator->handleAction(new Channel()));
    }

    public function testHandleActionWithNoErrors()
    {
        $event = $this->createMock(ChannelActionEvent::class);
        $event->expects(static::any())
            ->method('getErrors')
            ->willReturn(new ArrayCollection());

        $this->eventFactory->expects(static::once())
            ->method('create')
            ->willReturn($event);

        static::assertTrue($this->decorator->handleAction(new Channel()));
    }
}
