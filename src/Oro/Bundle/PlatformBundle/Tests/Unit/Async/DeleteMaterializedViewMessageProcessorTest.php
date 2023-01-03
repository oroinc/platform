<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Async;

use Oro\Bundle\PlatformBundle\Async\DeleteMaterializedViewMessageProcessor;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteMaterializedViewTopic;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DeleteMaterializedViewMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MaterializedViewManager|\PHPUnit\Framework\MockObject\MockObject */
    private $materializedViewManager;

    /** @var DeleteMaterializedViewMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->materializedViewManager = $this->createMock(MaterializedViewManager::class);

        $this->processor = new DeleteMaterializedViewMessageProcessor($this->materializedViewManager);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [DeleteMaterializedViewTopic::getName()],
            DeleteMaterializedViewMessageProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $message = new Message();
        $messageBody = ['materializedViewName' => 'sample-name'];
        $message->setBody($messageBody);

        $this->materializedViewManager->expects(self::once())
            ->method('delete')
            ->with($messageBody['materializedViewName']);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
