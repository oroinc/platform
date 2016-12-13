<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationsMessageProcessor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class UpdateEmailOwnerAssociationsMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfOwnerClassIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[UpdateEmailOwnerAssociationsMessageProcessor] Got invalid message: "{"ownerIds":[1]}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerIds' => [1],
        ]));

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfOwnerIdsIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[UpdateEmailOwnerAssociationsMessageProcessor] Got invalid message: "{"ownerClass":"class"}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
        ]));

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessUpdateEmailOwner()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('[UpdateEmailOwnerAssociationsMessageProcessor] Sent "2" messages'))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->at(0))
            ->method('send')
            ->with(Topics::UPDATE_EMAIL_OWNER_ASSOCIATION, ['ownerId' => 1,'ownerClass' => 'class'])
        ;
        $producer
            ->expects($this->at(1))
            ->method('send')
            ->with(Topics::UPDATE_EMAIL_OWNER_ASSOCIATION, ['ownerId' => 2,'ownerClass' => 'class'])
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
            'ownerIds' => [1,2],
        ]));

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $producer,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS],
            UpdateEmailOwnerAssociationsMessageProcessor::getSubscribedTopics()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }
}
