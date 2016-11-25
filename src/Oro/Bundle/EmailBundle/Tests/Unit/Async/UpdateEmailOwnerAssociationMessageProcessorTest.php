<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationMessageProcessor;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class UpdateEmailOwnerAssociationMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new UpdateEmailOwnerAssociationMessageProcessor(
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
            ->with('[UpdateEmailOwnerAssociationMessageProcessor] Got invalid message: "{"ownerId":[1]}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerId' => [1],
        ]));

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
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
            ->with('[UpdateEmailOwnerAssociationMessageProcessor] Got invalid message: "{"ownerClass":"class"}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
        ]));

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
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

        $manager = $this->createAssociationManagerMock();
        $manager
            ->expects($this->once())
            ->method('processUpdateEmailOwner')
            ->with('class', [1])
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
            'ownerId' => 1,
        ]));

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
            $manager,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::UPDATE_EMAIL_OWNER_ASSOCIATION],
            UpdateEmailOwnerAssociationMessageProcessor::getSubscribedTopics()
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
