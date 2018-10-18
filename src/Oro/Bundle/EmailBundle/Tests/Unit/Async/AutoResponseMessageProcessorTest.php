<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Async\AutoResponseMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AutoResponseMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AutoResponseMessageProcessor(
            $this->createDoctrineMock(),
            $this->createAutoResponseManagerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfBodyIsInvalid()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $processor = new AutoResponseMessageProcessor(
            $this->createDoctrineMock(),
            $this->createAutoResponseManagerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode(['key' => 'value']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendAutoResponse()
    {
        $email = new Email();

        $repository = $this->createEntityRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->will($this->returnValue($email))
        ;

        $doctrine  = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(Email::class)
            ->will($this->returnValue($repository))
        ;

        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->once())
            ->method('sendAutoResponses')
            ->with($this->identicalTo($email))
        ;


        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 123, 'jobId' => 4321]));

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(4321)
            ->will($this->returnCallback(function ($name, $callback) use ($email) {
                $callback($email);

                return true;
            }))
        ;

        $processor = new AutoResponseMessageProcessor(
            $doctrine,
            $autoResponseManager,
            $jobRunner,
            $this->createLoggerMock()
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectMessageIfEmailWasNotFound()
    {
        $repository = $this->createEntityRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->will($this->returnValue(null))
        ;

        $doctrine  = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(Email::class)
            ->will($this->returnValue($repository))
        ;

        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->never())
            ->method('sendAutoResponses')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Email was not found. id: "123"')
        ;

        $processor = new AutoResponseMessageProcessor(
            $doctrine,
            $autoResponseManager,
            $this->createJobRunnerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 123, 'jobId' => 4321]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::SEND_AUTO_RESPONSE], AutoResponseMessageProcessor::getSubscribedTopics());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AutoResponseManager
     */
    private function createAutoResponseManagerMock()
    {
        return $this->createMock(AutoResponseManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Registry
     */
    private function createDoctrineMock()
    {
        return $this->createMock(Registry::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityRepository
     */
    private function createEntityRepositoryMock()
    {
        return $this->createMock(EntityRepository::class);
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
