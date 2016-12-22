<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsByIdsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class PurgeEmailAttachmentsByIdsMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new PurgeEmailAttachmentsByIdsMessageProcessor(
            $this->createRegistryInterfaceMock(),
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
            ->with('[PurgeEmailAttachmentsByIdsMessageProcessor]'
                .' Got invalid message: "[]"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([]));

        $processor = new PurgeEmailAttachmentsByIdsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    private function createRegistryInterfaceMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
