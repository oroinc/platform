<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelEnableActionHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelEnableActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var ChannelEnableActionHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new ChannelEnableActionHandler($this->entityManager);
    }

    public function testHandleAction()
    {
        $this->entityManager->expects(static::once())->method('flush');

        $channel = new Channel();
        $channel->setEnabled(false);

        static::assertTrue($this->handler->handleAction($channel));
        static::assertTrue($channel->isEnabled());
        static::assertFalse($channel->getPreviouslyEnabled());
    }
}
