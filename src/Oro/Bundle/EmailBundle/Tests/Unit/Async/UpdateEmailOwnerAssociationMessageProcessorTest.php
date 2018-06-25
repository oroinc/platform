<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationMessageProcessor;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UpdateEmailOwnerAssociationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new UpdateEmailOwnerAssociationMessageProcessor(
            $this->createAssociationManagerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfOwnerClassIsMissing()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerId' => [1],
        ]));

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
            $this->createAssociationManagerMock(),
            $this->createJobRunnerMock(),
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
            ->with('Got invalid message')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'ownerClass' => 'class',
        ]));

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
            $this->createAssociationManagerMock(),
            $this->createJobRunnerMock(),
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

        $data = [
            'ownerClass' => 'class',
            'ownerId' => 1,
            'jobId' => 12345
        ];

        $message = new NullMessage();
        $message->setBody(json_encode($data));

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) use ($data) {
                $callback($data);

                return true;
            }))
        ;

        $processor = new UpdateEmailOwnerAssociationMessageProcessor(
            $manager,
            $jobRunner,
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
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AssociationManager
     */
    private function createAssociationManagerMock()
    {
        return $this->createMock(AssociationManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
