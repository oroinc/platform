<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\AddAssociationMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Command\Manager\AssociationManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AddAssociationMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AddAssociationMessageProcessor($this->createAssociationManagerMock(), $this->createLoggerMock());
    }

    public function testShouldRejectMessageIfEmailIdIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[AddAssociationMessageProcessor] Got invalid message: "{"targetClass":"class","targetId":123}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'targetClass' => 'class',
            'targetId' => 123,
        ]));

        $processor = new AddAssociationMessageProcessor(
            $this->createAssociationManagerMock(),
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
            ->with('[AddAssociationMessageProcessor] Got invalid message: "{"emailIds":[],"targetId":123}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [],
            'targetId' => 123,
        ]));

        $processor = new AddAssociationMessageProcessor(
            $this->createAssociationManagerMock(),
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
            ->with('[AddAssociationMessageProcessor] Got invalid message: "{"emailIds":[],"targetClass":"class"}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [],
            'targetClass' => 'class',
        ]));

        $processor = new AddAssociationMessageProcessor(
            $this->createAssociationManagerMock(),
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

        $manager = $this->createAssociationManagerMock();
        $manager
            ->expects($this->once())
            ->method('processAddAssociation')
            ->with([456], 'class', 123)
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'emailIds' => [456],
            'targetClass' => 'class',
            'targetId' => 123,
        ]));

        $processor = new AddAssociationMessageProcessor(
            $manager,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::ADD_ASSOCIATION], AddAssociationMessageProcessor::getSubscribedTopics());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AssociationManager
     */
    private function createAssociationManagerMock()
    {
        return $this->getMock(AssociationManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
