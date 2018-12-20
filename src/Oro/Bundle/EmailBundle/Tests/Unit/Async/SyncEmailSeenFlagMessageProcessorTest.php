<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Async\SyncEmailSeenFlagMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\EmailBundle\Manager\EmailFlagManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SyncEmailSeenFlagMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SyncEmailSeenFlagMessageProcessor(
            $this->createDoctrineMock(),
            $this->createEmailFlagManagerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfMessageIdPropertyIsNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $processor = new SyncEmailSeenFlagMessageProcessor(
            $this->createDoctrineMock(),
            $this->createEmailFlagManagerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode([
            'seen' => true,
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfMessageSeenPropertyIsNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $processor = new SyncEmailSeenFlagMessageProcessor(
            $this->createDoctrineMock(),
            $this->createEmailFlagManagerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode([
            'id' => 123,
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfUserEmailEntityWasNotFound()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('UserEmail was not found. id: "123"')
        ;

        $repository = $this->createEmailUserRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->will($this->returnValue(null))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(EmailUser::class)
            ->will($this->returnValue($repository))
        ;

        $flagManager = $this->createEmailFlagManagerMock();
        $flagManager
            ->expects($this->never())
            ->method('setSeen')
        ;
        $flagManager
            ->expects($this->never())
            ->method('setUnseen')
        ;

        $processor = new SyncEmailSeenFlagMessageProcessor(
            $doctrine,
            $flagManager,
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode([
            'id' => 123,
            'seen' => true,
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSetSeenIfSeenIsTrue()
    {
        $emailUser = new EmailUser();

        $logger = $this->createLoggerMock();

        $flagManager = $this->createEmailFlagManagerMock();
        $flagManager
            ->expects($this->once())
            ->method('changeStatusSeen')
            ->with($this->identicalTo($emailUser), true)
        ;

        $repository = $this->createEmailUserRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->will($this->returnValue($emailUser))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(EmailUser::class)
            ->will($this->returnValue($repository))
        ;
        $doctrine
            ->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(EmailUser::class)
            ->will($this->returnValue($em))
        ;

        $processor = new SyncEmailSeenFlagMessageProcessor($doctrine, $flagManager, $logger);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'id' => 123,
            'seen' => true,
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldSetUnseenIfSeenIsFalse()
    {
        $emailUser = new EmailUser();

        $logger = $this->createLoggerMock();

        $flagManager = $this->createEmailFlagManagerMock();

        $flagManager
            ->expects($this->once())
            ->method('changeStatusSeen')
            ->with($this->identicalTo($emailUser), false)
        ;

        $repository = $this->createEmailUserRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->will($this->returnValue($emailUser))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(EmailUser::class)
            ->will($this->returnValue($repository))
        ;
        $doctrine
            ->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(EmailUser::class)
            ->will($this->returnValue($em))
        ;

        $processor = new SyncEmailSeenFlagMessageProcessor($doctrine, $flagManager, $logger);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'id' => 123,
            'seen' => false,
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::SYNC_EMAIL_SEEN_FLAG], SyncEmailSeenFlagMessageProcessor::getSubscribedTopics());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EmailUserRepository
     */
    private function createEmailUserRepositoryMock()
    {
        return $this->createMock(EmailUserRepository::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EmailFlagManager
     */
    private function createEmailFlagManagerMock()
    {
        return $this->createMock(EmailFlagManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Registry
     */
    private function createDoctrineMock()
    {
        return $this->createMock(Registry::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
