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
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AutoResponseMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AutoResponseMessageProcessor(
            $this->createMock(Registry::class),
            $this->createMock(AutoResponseManager::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfBodyIsInvalid()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new AutoResponseMessageProcessor(
            $this->createMock(Registry::class),
            $this->createMock(AutoResponseManager::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $message = new Message();
        $message->setBody(json_encode(['key' => 'value'], JSON_THROW_ON_ERROR));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendAutoResponse()
    {
        $email = new Email();

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($email);

        $doctrine  = $this->createMock(Registry::class);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($repository);

        $autoResponseManager = $this->createMock(AutoResponseManager::class);
        $autoResponseManager->expects($this->once())
            ->method('sendAutoResponses')
            ->with($this->identicalTo($email));

        $message = new Message();
        $message->setBody(json_encode(['id' => 123, 'jobId' => 4321], JSON_THROW_ON_ERROR));

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(4321)
            ->willReturnCallback(function ($name, $callback) use ($email) {
                $callback($email);

                return true;
            });

        $processor = new AutoResponseMessageProcessor(
            $doctrine,
            $autoResponseManager,
            $jobRunner,
            $this->createMock(LoggerInterface::class)
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectMessageIfEmailWasNotFound()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $doctrine = $this->createMock(Registry::class);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($repository);

        $autoResponseManager = $this->createMock(AutoResponseManager::class);
        $autoResponseManager->expects($this->never())
            ->method('sendAutoResponses');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Email was not found. id: "123"');

        $processor = new AutoResponseMessageProcessor(
            $doctrine,
            $autoResponseManager,
            $this->createMock(JobRunner::class),
            $logger
        );

        $message = new Message();
        $message->setBody(json_encode(['id' => 123, 'jobId' => 4321], JSON_THROW_ON_ERROR));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::SEND_AUTO_RESPONSE], AutoResponseMessageProcessor::getSubscribedTopics());
    }
}
