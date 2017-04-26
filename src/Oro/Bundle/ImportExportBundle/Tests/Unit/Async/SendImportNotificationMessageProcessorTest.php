<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;

use Oro\Bundle\ImportExportBundle\Async\SendImportNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SendImportNotificationMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testSendImportNotificationProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new SendImportNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createJobStorageMock(),
            $this->createImportJobSummaryResultServiceMock(),
            $this->createConfigManagerMock(),
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
            $this->createConfigManagerMock(),
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
            $this->createConfigManagerMock(),
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
            ->with($job, 'import.csv', ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT)
            ->willReturn(['Subject of import email', 'summary import information']);
        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_notification.email_notification_sender_email')
            ->willReturn('test@mail.com');
        $configManager
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_notification.email_notification_sender_name')
            ->willReturn('John');
        $producer = $this->createMessageProducerInterfaceMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(
                NotificationTopics::SEND_NOTIFICATION_EMAIL,
                [
                    'fromEmail' => 'test@mail.com',
                    'fromName' => 'John',
                    'toEmail' => $user->getEmail(),
                    'subject' => 'Subject of import email',
                    'body' => 'summary import information',
                    'contentType' => 'text/html',
                ]
            );
        $processor = new SendImportNotificationMessageProcessor(
            $producer,
            $logger,
            $jobStorage,
            $consolidateImportJobResultNotification,
            $configManager,
            $doctrine
        );
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
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
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducerInterfaceMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerInterfaceMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    protected function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ImportExportResultSummarizer
     */
    protected function createImportJobSummaryResultServiceMock()
    {
        return $this->createMock(ImportExportResultSummarizer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->createMock(ConfigManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageInterface
     */
    private function createMessageMock()
    {
        return $this->createMock(MessageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UserRepository
     */
    private function createUserRepositoryMock()
    {
        return $this->createMock(UserRepository::class);
    }
}
