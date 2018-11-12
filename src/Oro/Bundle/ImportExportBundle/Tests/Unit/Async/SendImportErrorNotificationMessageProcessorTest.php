<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\SendImportErrorNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SendImportErrorNotificationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testSendImportNotificationProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new SendImportErrorNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createNotificationSettingsMock(),
            $this->createDoctrineMock()
        );

        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportMessageProcessor);
    }

    public function testSendImportNotificationProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::SEND_IMPORT_ERROR_NOTIFICATION,];
        $this->assertEquals(
            $expectedSubscribedTopics,
            SendImportErrorNotificationMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Invalid message')
        ;

        $processor = new SendImportErrorNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $this->createNotificationSettingsMock(),
            $this->createDoctrineMock()
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn('[]')
        ;
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogErrorAndRejectMessageIfUserNotFound()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('User not found. Id: 1')
        ;

        $userRepo = $this->createUserRepositoryMock();
        $userRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($userRepo))
        ;
        $processor = new SendImportErrorNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $this->createNotificationSettingsMock(),
            $doctrine
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                        'file' => 'file' ,
                        'error' => 'error test',
                        'userId' => 1,
                       ]))
        ;
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessAndReturnAckWithUserId()
    {
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $user->setEmail('user@email.com');
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Sent notification message.');
        $logger
            ->expects($this->any())
            ->method('error');
        $logger
            ->expects($this->any())
            ->method('critical');

        $userRepo = $this->createUserRepositoryMock();
        $userRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);
        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($userRepo));

        $sender = From::emailAddress('test@mail.com', 'John');
        $notificationSettings = $this->createNotificationSettingsMock();
        $notificationSettings
            ->expects($this->once())
            ->method('getSender')
            ->willReturn(From::emailAddress('test@mail.com', 'John'));

        $producer = $this->createMessageProducerInterfaceMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(
                NotificationTopics::SEND_NOTIFICATION_EMAIL,
                [
                    'sender' => $sender->toArray(),
                    'toEmail' => $user->getEmail(),
                    'subject' => 'Cannot Import file import.csv',
                    'body' => 'error import',
                ]
            );

        $processor = new SendImportErrorNotificationMessageProcessor(
            $producer,
            $logger,
            $notificationSettings,
            $doctrine
        );
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                        'file' => 'import.csv' ,
                        'error' => 'error import',
                        'userId' => 1,
                    ]));
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldProcessAndReturnAckWithNotifyEmail()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Sent notification message.');
        $logger
            ->expects($this->any())
            ->method('error');
        $logger
            ->expects($this->any())
            ->method('critical');

        $sender = From::emailAddress('test@mail.com', 'John');
        $notificationSettings = $this->createNotificationSettingsMock();
        $notificationSettings
            ->expects($this->once())
            ->method('getSender')
            ->willReturn(From::emailAddress('test@mail.com', 'John'));

        $producer = $this->createMessageProducerInterfaceMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(
                NotificationTopics::SEND_NOTIFICATION_EMAIL,
                [
                    'sender' => $sender->toArray(),
                    'toEmail' => 'test@mail.com',
                    'subject' => 'Cannot Import file import.csv',
                    'body' => 'error import',
                ]
            );

        $processor = new SendImportErrorNotificationMessageProcessor(
            $producer,
            $logger,
            $notificationSettings,
            $this->createDoctrineMock()
        );
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                        'file' => 'import.csv' ,
                        'error' => 'error import',
                        'notifyEmail' => 'test@mail.com',
                    ]));
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    protected function createMessageProducerInterfaceMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected function createLoggerInterfaceMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|NotificationSettings
     */
    private function createNotificationSettingsMock()
    {
        return $this->createMock(NotificationSettings::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageInterface
     */
    private function createMessageMock()
    {
        return $this->createMock(MessageInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserRepository
     */
    private function createUserRepositoryMock()
    {
        return $this->createMock(UserRepository::class);
    }
}
