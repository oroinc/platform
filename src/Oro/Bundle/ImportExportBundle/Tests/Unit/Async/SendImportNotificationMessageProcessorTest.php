<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\SendImportNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
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
    public function testSendImportNotificationProcessCanBeConstructedWithRequiredAttributes(): void
    {
        $chunkHttpImportMessageProcessor = new SendImportNotificationMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ImportExportResultSummarizer::class),
            $this->createMock(NotificationSettings::class),
            $this->createMock(ManagerRegistry::class)
        );

        self::assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        self::assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportMessageProcessor);
    }

    public function testSendImportNotificationProcessShouldReturnSubscribedTopics(): void
    {
        self::assertEquals(
            [SendImportNotificationTopic::getName()],
            SendImportNotificationMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldLogErrorAndRejectMessageIfUserNotFound(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('User not found. Id: 1');

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository->expects(self::once())
            ->method('findJobById')
            ->with(1)
            ->willReturn(new Job());

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([[User::class, null, $userRepo], [Job::class, null, $jobRepository]]);

        $processor = new SendImportNotificationMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $logger,
            $this->createMock(ImportExportResultSummarizer::class),
            $this->createMock(NotificationSettings::class),
            $doctrine
        );

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'rootImportJobId' => 1,
                'originFileName' => 'originFileName',
                'userId' => 1,
                'process' => ProcessorRegistry::TYPE_IMPORT,
            ]);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessAndReturnAck(): void
    {
        $job = new Job();
        $job->setId(1);
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $user->setEmail('user@email.com');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Sent notification message.');
        $logger->expects(self::any())
            ->method('error');
        $logger->expects(self::any())
            ->method('critical');

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository->expects(self::once())
            ->method('findJobById')
            ->with(1)
            ->willReturn($job);
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($user);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([[User::class, null, $userRepo], [Job::class, null, $jobRepository]]);
        $consolidateImportJobResultNotification = $this->createMock(ImportExportResultSummarizer::class);
        $consolidateImportJobResultNotification->expects(self::once())
            ->method('getSummaryResultForNotification')
            ->with($job, 'import.csv')
            ->willReturn(['data' => 'summary import information']);

        $sender = From::emailAddress('test@mail.com', 'John');
        $notificationsSettings = $this->createMock(NotificationSettings::class);
        $notificationsSettings->expects(self::once())
            ->method('getSender')
            ->willReturn($sender);

        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects(self::once())
            ->method('send')
            ->with(
                SendEmailNotificationTemplateTopic::getName(),
                [
                    'from' => $sender->toString(),
                    'templateParams' => ['data' => 'summary import information'],
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
        $message->setBody([
            'rootImportJobId' => 1,
            'originFileName' => 'import.csv',
            'userId' => 1,
            'process' => ProcessorRegistry::TYPE_IMPORT,
        ]);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
