<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler;

use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelDeleteActionHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;

class ChannelDeleteActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeleteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteManager;

    /**
     * @var ChannelDeleteActionHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->deleteManager = $this->createMock(DeleteManager::class);

        $this->handler = new ChannelDeleteActionHandler($this->deleteManager);
    }

    public function testHandleAction()
    {
        $this->deleteManager->expects(static::once())
            ->method('delete')
            ->willReturn(false);

        static::assertFalse($this->handler->handleAction(new Channel()));
    }
}
