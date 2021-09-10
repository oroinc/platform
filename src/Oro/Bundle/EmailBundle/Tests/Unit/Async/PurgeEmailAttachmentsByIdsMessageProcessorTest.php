<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsByIdsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class PurgeEmailAttachmentsByIdsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new PurgeEmailAttachmentsByIdsMessageProcessor(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::PURGE_EMAIL_ATTACHMENTS_BY_IDS],
            PurgeEmailAttachmentsByIdsMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectMessageIfIdsIsMissing()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $message = new Message();
        $message->setBody(json_encode([], JSON_THROW_ON_ERROR));

        $processor = new PurgeEmailAttachmentsByIdsMessageProcessor(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
