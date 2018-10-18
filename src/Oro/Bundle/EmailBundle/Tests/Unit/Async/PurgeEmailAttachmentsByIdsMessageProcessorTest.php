<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsByIdsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PurgeEmailAttachmentsByIdsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new PurgeEmailAttachmentsByIdsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
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
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([]));

        $processor = new PurgeEmailAttachmentsByIdsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }


    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    private function createRegistryInterfaceMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
