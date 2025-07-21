<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelActionHandlerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\Decorator\ChannelActionHandlerTransactionDecorator;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChannelActionHandlerTransactionDecoratorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ChannelActionHandlerInterface&MockObject $actionHandler;
    private ChannelActionHandlerTransactionDecorator $decorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->actionHandler = $this->createMock(ChannelActionHandlerInterface::class);

        $this->decorator = new ChannelActionHandlerTransactionDecorator(
            $this->entityManager,
            $this->actionHandler
        );
    }

    public function testHandleActionWithError(): void
    {
        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::once())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');

        $this->actionHandler->expects(self::once())
            ->method('handleAction')
            ->willReturn(false);

        self::assertFalse($this->decorator->handleAction(new Channel()));
    }

    public function testHandleActionWithNoError(): void
    {
        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::never())
            ->method('rollback');
        $this->entityManager->expects(self::once())
            ->method('commit');

        $this->actionHandler->expects(self::once())
            ->method('handleAction')
            ->willReturn(true);

        self::assertTrue($this->decorator->handleAction(new Channel()));
    }
}
