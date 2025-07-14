<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler;

use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelDeleteActionHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChannelDeleteActionHandlerTest extends TestCase
{
    private DeleteManager&MockObject $deleteManager;
    private ChannelDeleteActionHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->deleteManager = $this->createMock(DeleteManager::class);

        $this->handler = new ChannelDeleteActionHandler($this->deleteManager);
    }

    public function testHandleAction(): void
    {
        $this->deleteManager->expects(self::once())
            ->method('delete')
            ->willReturn(false);

        self::assertFalse($this->handler->handleAction(new Channel()));
    }
}
