<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\SendImportNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SendImportNotificationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testSendImportNotificationProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new SendImportNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createJobStorageMock(),
            $this->createImportJobSummaryResultServiceMock(),
            $this->createNotificationSettings(),
            $this->createDoctrineMock()
        );

        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportMessageProcessor);
    }

    public function testSendImportNotificationProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::SEND_IMPORT_NOTIFICATION,];
        $this->assertEquals($expectedSubscribedTopics, SendImportNotificationMessageProcessor::getSubscribedTopics());
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Invalid message')
        ;

        $processor = new SendImportNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $this->createJobStorageMock(),
            $this->createImportJobSummaryResultServiceMock(),
            $this->createNotificationSettings(),
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

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn(new Job())
            ;

        $processor = new SendImportNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $jobStorage,
            $this->createImportJobSummaryResultServiceMock(),
            $this->createNotificationSettings(),
            $doctrine
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                        'rootImportJobId' => 1,
                        'filePath' => 'filePath',
                        'originFileName' => 'originFileName',
                        'userId' => 1,
                        'process' => ProcessorRegistry::TYPE_IMPORT,
                       ]))
        ;
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessAndReturnAck()
    {
        $job = new Job();
        $job->setId(1);
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
        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn($job);
        $consolidateImportJobResultNotification = $this->createImportJobSummaryResultServiceMock();
        $consolidateImportJobResultNotification
            ->expects($this->once())
            ->method('getSummaryResultForNotification')
            ->with($job, 'import.csv')
            ->willReturn(['data' => 'summary import information']);

        $sender = From::emailAddress('test@mail.com', 'John');
        $notificationsSettings = $this->createNotificationSettings();
        $notificationsSettings
            ->expects($this->once())
            ->method('getSender')
            ->willReturn($sender);

        $producer = $this->createMessageProducerInterfaceMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo(NotificationTopics::SEND_NOTIFICATION_EMAIL),
                $this->equalTo([
                    'sender' => $sender->toArray(),
                    'toEmail' => $user->getEmail(),
                    'body' => ['data' => 'summary import information'],
                    'contentType' => 'text/html',
                    'recipientUserId' => 1,
                    'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT,
                ])
            );
        $processor = new SendImportNotificationMessageProcessor(
            $producer,
            $logger,
            $jobStorage,
            $consolidateImportJobResultNotification,
            $notificationsSettings,
            $doctrine
        );
        $message = new NullMessage();
        $message->setBody(json_encode([
            'rootImportJobId' => 1,
            'filePath' => 'filePath',
            'originFileName' => 'import.csv',
            'userId' => 1,
            'process' => ProcessorRegistry::TYPE_IMPORT,
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
     * @return \PHPUnit\Framework\MockObject\MockObject|JobStorage
     */
    protected function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ImportExportResultSummarizer
     */
    protected function createImportJobSummaryResultServiceMock()
    {
        return $this->createMock(ImportExportResultSummarizer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|NotificationSettings
     */
    private function createNotificationSettings()
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
