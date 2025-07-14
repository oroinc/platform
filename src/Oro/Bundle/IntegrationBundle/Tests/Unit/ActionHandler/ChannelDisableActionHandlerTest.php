<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelDisableActionHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChannelDisableActionHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ChannelDisableActionHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new ChannelDisableActionHandler($this->entityManager);
    }

    public function testHandleAction(): void
    {
        $this->entityManager->expects(self::once())
            ->method('flush');

        $channel = new Channel();
        $channel->setEnabled(true);

        self::assertTrue($this->handler->handleAction($channel));
        self::assertFalse($channel->isEnabled());
        self::assertTrue($channel->getPreviouslyEnabled());
    }
}
