<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\ConsolidateImportJobResultNotificationService;
use Oro\Bundle\ImportExportBundle\Async\SendImportNotificationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
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

class SendImportNotificationMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testSendImportNotificationProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new SendImportNotificationMessageProcessor(
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createJobStorageMock(),
            $this->createConsolidateImportJobResultNotificationServiceMock(),
            $this->createConfigManagerMock(),
            $this->createTranslatorInterfaceMock(),
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
            $this->createConsolidateImportJobResultNotificationServiceMock(),
            $this->createConfigManagerMock(),
            $this->createTranslatorInterfaceMock(),
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
            $this->createConsolidateImportJobResultNotificationServiceMock(),
            $this->createConfigManagerMock(),
            $this->createTranslatorInterfaceMock(),
            $doctrine
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                        'rootImportJobId' => 1,
                        'filePath' => 'filePath' ,
                        'originFileName' => 'originFileName',
                        'userId' => 1,
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
        $consolidateImportJobResultNotification = $this->createConsolidateImportJobResultNotificationServiceMock();
           $consolidateImportJobResultNotification
            ->expects($this->once())
            ->method('getImportSummary')
            ->with($job, 'import.csv')
            ->willReturn('summary import information');
        $translator = $this->createTranslatorInterfaceMock();
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('oro.importexport.import.async_import', ['%origin_file_name%' => 'import.csv'])
            ->willReturn('Subject of import email');
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
                    'body' => 'summary import information'
                ]
            );
        $processor = new SendImportNotificationMessageProcessor(
            $producer,
            $logger,
            $jobStorage,
            $consolidateImportJobResultNotification,
            $configManager,
            $translator,
            $doctrine
        );
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                        'rootImportJobId' => 1,
                        'filePath' => 'filePath' ,
                        'originFileName' => 'import.csv',
                        'userId' => 1,
                        'subscribedTopic' => [Topics::IMPORT_HTTP_PREPARING,]
                    ]));
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducerInterfaceMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerInterfaceMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    protected function createJobStorageMock()
    {
        return $this->getMock(JobStorage::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConsolidateImportJobResultNotificationService
     */
    protected function createConsolidateImportJobResultNotificationServiceMock()
    {
        return $this->getMock(ConsolidateImportJobResultNotificationService::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->getMock(ConfigManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private function createTranslatorInterfaceMock()
    {
        return $this->getMock(TranslatorInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->getMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageInterface
     */
    private function createMessageMock()
    {
        return $this->getMock(MessageInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UserRepository
     */
    private function createUserRepositoryMock()
    {
        return $this->getMock(UserRepository::class, [], [], '', false);
    }
}
