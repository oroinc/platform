<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UpdateEmailOwnerAssociationsMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createAssociationManagerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfOwnerClassIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[UpdateEmailOwnerAssociationsMessageProcessor] Got invalid message: "{"ownerIds":[123]}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerIds' => [123],
        ]));

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $this->createAssociationManagerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfOwnerIdIsMissing()
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
            $this->createAssociationManagerMock(),
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
            ->with($this->equalTo('[UpdateEmailOwnerAssociationsMessageProcessor] Sent "1" messages'))
        ;

        $manager = $this->createAssociationManagerMock();
        $manager
            ->expects($this->once())
            ->method('processUpdateEmailOwner')
            ->with('class', [123])
            ->willReturn(1)
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
            'ownerIds' => [123],
        ]));

        $processor = new UpdateEmailOwnerAssociationsMessageProcessor(
            $manager,
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
