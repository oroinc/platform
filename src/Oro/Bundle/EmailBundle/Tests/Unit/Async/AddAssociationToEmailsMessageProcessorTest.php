<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Async\AddAssociationToEmailsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class AddAssociationToEmailsMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AddAssociationToEmailsMessageProcessor($this->createMessageProducerMock(), $this->createLoggerMock());
    }

    public function testShouldRejectMessageIfEmailIdsIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[AddAssociationToEmailsMessageProcessor]'
                .' Got invalid message: "{"targetClass":"class","targetId":123}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'targetClass' => 'class',
            'targetId' => 123,
        ]));

        $processor = new AddAssociationToEmailsMessageProcessor(
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfTargetClassMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[AddAssociationToEmailsMessageProcessor] Got invalid message: "{"emailIds":[],"targetId":123}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [],
            'targetId' => 123,
        ]));

        $processor = new AddAssociationToEmailsMessageProcessor(
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfTargetIdMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[AddAssociationToEmailsMessageProcessor]'
                .' Got invalid message: "{"emailIds":[],"targetClass":"class"}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [],
            'targetClass' => 'class',
        ]));

        $processor = new AddAssociationToEmailsMessageProcessor(
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessAddAssociation()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('[AddAssociationToEmailsMessageProcessor] Sent "2" messages'))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->at(0))
            ->method('send')
            ->with(Topics::ADD_ASSOCIATION_TO_EMAIL, ['emailId' => 1,'targetClass' => 'class','targetId' => 123])
        ;
        $producer
            ->expects($this->at(1))
            ->method('send')
            ->with(Topics::ADD_ASSOCIATION_TO_EMAIL, ['emailId' => 2,'targetClass' => 'class','targetId' => 123])
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [1,2],
            'targetClass' => 'class',
            'targetId' => 123,
        ]));

        $processor = new AddAssociationToEmailsMessageProcessor(
            $producer,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::ADD_ASSOCIATION_TO_EMAILS],
            AddAssociationToEmailsMessageProcessor::getSubscribedTopics()
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
