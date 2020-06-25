<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\SendImportNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SendImportNotificationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testSendImportNotificationProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new SendImportNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
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

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository
            ->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn(new Job());

        $manager = $this->createDoctrineMock();
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($jobRepository);

        $userRepo = $this->createUserRepositoryMock();
        $userRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Job::class)
            ->willReturn($manager);
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($userRepo))
        ;

        $processor = new SendImportNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $logger,
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
        $logger->expects($this->once())
            ->method('info')
            ->with('Sent notification message.');
        $logger->expects($this->any())
            ->method('error');
        $logger->expects($this->any())
            ->method('critical');

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository
            ->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn($job);
        $manager = $this->createDoctrineMock();
        $manager->expects($this->once())->method('getRepository')
            ->with(Job::class)
            ->willReturn($jobRepository);
        $userRepo = $this->createUserRepositoryMock();
        $userRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);
        $doctrine = $this->createDoctrineMock();
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Job::class)
            ->willReturn($manager);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($userRepo));
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
            $consolidateImportJobResultNotification,
            $notificationsSettings,
            $doctrine
        );
        $message = new Message();
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
     * @return \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(ManagerRegistry::class);
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
