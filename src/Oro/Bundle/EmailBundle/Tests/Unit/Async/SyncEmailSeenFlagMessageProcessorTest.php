<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\SyncEmailSeenFlagMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topic\SyncEmailSeenFlagTopic;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Manager\EmailFlagManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SyncEmailSeenFlagMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $this->expectNotToPerformAssertions();

        new SyncEmailSeenFlagMessageProcessor(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailFlagManager::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfUserEmailEntityWasNotFound()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('UserEmail was not found. id: "123"');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(EmailUser::class, 123)
            ->willReturn(null);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailUser::class)
            ->willReturn($em);

        $flagManager = $this->createMock(EmailFlagManager::class);
        $flagManager->expects($this->never())
            ->method('setSeen');
        $flagManager->expects($this->never())
            ->method('setUnseen');

        $processor = new SyncEmailSeenFlagMessageProcessor(
            $doctrine,
            $flagManager,
            $logger
        );

        $message = new Message();
        $message->setBody(['id' => 123, 'seen' => true]);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSetSeenIfSeenIsTrue()
    {
        $emailUser = new EmailUser();

        $logger = $this->createMock(LoggerInterface::class);

        $flagManager = $this->createMock(EmailFlagManager::class);
        $flagManager->expects($this->once())
            ->method('changeStatusSeen')
            ->with($this->identicalTo($emailUser), true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(EmailUser::class, 123)
            ->willReturn($emailUser);
        $em->expects($this->once())
            ->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailUser::class)
            ->willReturn($em);

        $processor = new SyncEmailSeenFlagMessageProcessor($doctrine, $flagManager, $logger);

        $message = new Message();
        $message->setBody(['id' => 123, 'seen' => true]);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldSetUnseenIfSeenIsFalse()
    {
        $emailUser = new EmailUser();

        $logger = $this->createMock(LoggerInterface::class);

        $flagManager = $this->createMock(EmailFlagManager::class);
        $flagManager->expects($this->once())
            ->method('changeStatusSeen')
            ->with($this->identicalTo($emailUser), false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(EmailUser::class, 123)
            ->willReturn($emailUser);
        $em->expects($this->once())
            ->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailUser::class)
            ->willReturn($em);

        $processor = new SyncEmailSeenFlagMessageProcessor($doctrine, $flagManager, $logger);

        $message = new Message();
        $message->setBody(['id' => 123, 'seen' => false]);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        self::assertEquals(
            [SyncEmailSeenFlagTopic::getName()],
            SyncEmailSeenFlagMessageProcessor::getSubscribedTopics()
        );
    }
}
