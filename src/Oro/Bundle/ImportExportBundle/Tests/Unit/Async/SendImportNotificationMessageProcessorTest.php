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
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SendImportNotificationMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testSendImportNotificationProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new SendImportNotificationMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ImportExportResultSummarizer::class),
            $this->createMock(NotificationSettings::class),
            $this->createMock(ManagerRegistry::class)
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
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Invalid message');

        $processor = new SendImportNotificationMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $logger,
            $this->createMock(ImportExportResultSummarizer::class),
            $this->createMock(NotificationSettings::class),
            $this->createMock(ManagerRegistry::class)
        );

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn('[]');
        $result = $processor->process($message, $this->createMock(SessionInterface::class));
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogErrorAndRejectMessageIfUserNotFound()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('User not found. Id: 1');

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn(new Job());

        $manager = $this->createMock(ManagerRegistry::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($jobRepository);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Job::class)
            ->willReturn($manager);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepo);

        $processor = new SendImportNotificationMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $logger,
            $this->createMock(ImportExportResultSummarizer::class),
            $this->createMock(NotificationSettings::class),
            $doctrine
        );

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'rootImportJobId' => 1,
                'filePath' => 'filePath',
                'originFileName' => 'originFileName',
                'userId' => 1,
                'process' => ProcessorRegistry::TYPE_IMPORT,
            ]));
        $result = $processor->process($message, $this->createMock(SessionInterface::class));
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
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Sent notification message.');
        $logger->expects($this->any())
            ->method('error');
        $logger->expects($this->any())
            ->method('critical');

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn($job);
        $manager = $this->createMock(ManagerRegistry::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($jobRepository);
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Job::class)
            ->willReturn($manager);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepo);
        $consolidateImportJobResultNotification = $this->createMock(ImportExportResultSummarizer::class);
        $consolidateImportJobResultNotification->expects($this->once())
            ->method('getSummaryResultForNotification')
            ->with($job, 'import.csv')
            ->willReturn(['data' => 'summary import information']);

        $sender = From::emailAddress('test@mail.com', 'John');
        $notificationsSettings = $this->createMock(NotificationSettings::class);
        $notificationsSettings->expects($this->once())
            ->method('getSender')
            ->willReturn($sender);

        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects($this->once())
            ->method('send')
            ->with(
                NotificationTopics::SEND_NOTIFICATION_EMAIL,
                [
                    'sender' => $sender->toArray(),
                    'toEmail' => $user->getEmail(),
                    'body' => ['data' => 'summary import information'],
                    'contentType' => 'text/html',
                    'recipientUserId' => 1,
                    'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT,
                ]
            );
        $processor = new SendImportNotificationMessageProcessor(
            $producer,
            $logger,
            $consolidateImportJobResultNotification,
            $notificationsSettings,
            $doctrine
        );
        $message = new Message();
        $message->setBody(JSON::encode([
            'rootImportJobId' => 1,
            'filePath' => 'filePath',
            'originFileName' => 'import.csv',
            'userId' => 1,
            'process' => ProcessorRegistry::TYPE_IMPORT,
        ]));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
