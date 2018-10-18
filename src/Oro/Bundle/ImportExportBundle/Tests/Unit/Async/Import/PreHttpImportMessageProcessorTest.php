<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class PreparingHttpImportMessageProcessorTest
 * @package Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import
 */
class PreHttpImportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    private const USER_ID = 32;

    public function testImportProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createDependentJobMock(),
            $this->createFileManagerMock(),
            $this->createHttpImportHandlerMock(),
            $this->createWriterChainMock(),
            $this->createNotificationsettingsStub(),
            100
        );

        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportMessageProcessor);
    }

    public function testImportProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [
            Topics::PRE_HTTP_IMPORT,
        ];
        $this->assertEquals($expectedSubscribedTopics, PreHttpImportMessageProcessor::getSubscribedTopics());
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $processor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $this->createDependentJobMock(),
            $this->createFileManagerMock(),
            $this->createHttpImportHandlerMock(),
            $this->createWriterChainMock(),
            $this->createNotificationsettingsStub(),
            100
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

    public function testShouldLogWarningAndUseDefaultIfSplitterNotFound()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Not supported format: "test", using default')
        ;
        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.test')
            ->willReturn('12345.test');
        $fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with('123435.test');

        $writer = $this->createWriterMock();
        $writerChain = new WriterChain();
        $writerChain->addWriter($writer, 'csv');

        $importHandler = $this->createHttpImportHandlerMock();
        $importHandler
            ->expects($this->once())
            ->method('splitImportFile')
            ->willReturn(['test']);

        $processor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $this->createDependentJobMock(),
            $fileManager,
            $importHandler,
            $writerChain,
            $this->createNotificationsettingsStub(),
            100
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'fileName' => '123435.test',
                'originFileName' => 'test.test',
                'userId' => '1',
                'jobName' => 'test',
                'processorAlias' => 'test',
                'process' => 'import',
                'options' => [],
            ]))
        ;

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunRunUniqueAndACKMessage()
    {
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(1)
            ->willReturn(true)
            ;
        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.csv')
            ->willReturn('12345.csv');

        $writer = $this->createWriterMock();
        $writerChain = new WriterChain();
        $writerChain->addWriter($writer, 'csv');

        $options = [
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_DELIMITER => ';',
        ];

        $handler = $this->createHttpImportHandlerMock();
        $handler
            ->expects($this->once())
            ->method('setImportingFileName')
            ->with('12345.csv')
        ;
        $handler
            ->expects($this->once())
            ->method('setConfigurationOptions')
            ->with([
                Context::OPTION_ENCLOSURE => '|',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_BATCH_SIZE => 100,
            ])
        ;
        $handler
            ->expects($this->once())
            ->method('splitImportFile')
            ->with('test', 'import', $writer)
            ->willReturn(['import_1.csv'])
        ;

        $processor = new PreHttpImportMessageProcessor(
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createDependentJobMock(),
            $fileManager,
            $handler,
            $writerChain,
            $this->createNotificationsettingsStub(),
            100
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'fileName' => '123435.csv',
                'originFileName' => 'test.csv',
                'userId' => '1',
                'jobName' => 'test',
                'processorAlias' => 'test',
                'process' => 'import',
                'options' => $options,
            ]))
        ;

        $message
            ->expects($this->once())
            ->method('getMessageId')
            ->willReturn(1);

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testUniqueJobWithCustomName()
    {
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(1, 'oro:import:test:test:0')
            ->willReturn(true)
            ;
        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.csv')
            ->willReturn('12345.csv');

        $writer = $this->createWriterMock();
        $writerChain = new WriterChain();
        $writerChain->addWriter($writer, 'csv');

        $options = [
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_DELIMITER => ';',
            'unique_job_slug' => 0,
        ];

        $handler = $this->createHttpImportHandlerMock();
        $handler
            ->expects($this->once())
            ->method('setImportingFileName')
            ->with('12345.csv')
        ;
        $handler
            ->expects($this->once())
            ->method('setConfigurationOptions')
            ->with([
                Context::OPTION_ENCLOSURE => '|',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_BATCH_SIZE => 100,
                'unique_job_slug' => 0,
            ])
        ;
        $handler
            ->expects($this->once())
            ->method('splitImportFile')
            ->with('test', 'import', $writer)
            ->willReturn(['import_1.csv'])
        ;

        $processor = new PreHttpImportMessageProcessor(
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createDependentJobMock(),
            $fileManager,
            $handler,
            $writerChain,
            $this->createNotificationsettingsStub(),
            100
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'fileName' => '123435.csv',
                'originFileName' => 'test.csv',
                'userId' => '1',
                'jobName' => 'test',
                'processorAlias' => 'test',
                'process' => 'import',
                'options' => $options,
            ]))
        ;

        $message
            ->expects($this->once())
            ->method('getMessageId')
            ->willReturn(1);

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectMessageAndSendErrorNotification()
    {
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'fileName' => '12345.csv',
                'originFileName' => 'test.csv',
                'userId' => '1',
                'jobName' => 'test',
                'processorAlias' => 'test',
                'process' => 'import',
                'options' => [],
            ]));

        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('12345.csv')
            ->willReturn('12345.csv');

        $writer = $this->createWriterMock();
        $writerChain = new WriterChain();
        $writerChain->addWriter($writer, 'csv');

        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('An error occurred while reading file test.csv: "test Error"');

        $producer = $this->createMessageProducerInterfaceMock();
        $expectedSender = From::emailAddress('sender_email@example.com', 'sender_name');
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(
                NotificationTopics::SEND_NOTIFICATION_EMAIL,
                [
                    'sender' => $expectedSender->toArray(),
                    'toEmail' => 'useremail@example.com',
                    'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_ERROR,
                    'body' => [
                        'originFileName' => 'test.csv',
                        'error' => 'The import file could not be imported due to a fatal error. ' .
                                   'Please check its integrity and try again!',
                    ],
                    'recipientUserId' => self::USER_ID,
                    'contentType' => 'text/html',
                ]
            );

        $handler = $this->createHttpImportHandlerMock();
        $handler
            ->expects($this->once())
            ->method('splitImportFile')
            ->willThrowException(new \Exception('test Error'));

        $processor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $producer,
            $logger,
            $this->createDependentJobMock(),
            $fileManager,
            $handler,
            $writerChain,
            $this->createNotificationsettingsStub(),
            100
        );

        $user = $this->getEntity(User::class, ['id' => self::USER_ID, 'email' => 'useremail@example.com']);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo(1))
            ->willReturn($user);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($userRepository);

        $managerRegistry = $this->createManagerRegistry();
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $processor->setManagerRegistry($managerRegistry);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldProcessPreparingMessageAndSendImportAndNotificationMessagesAndACKMessage()
    {
        $messageData = [
            'fileName' => '12345.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobName' => 'test_import',
            'processorAlias' => 'processor_test',
            'process' => 'import',
            'options' => [
                'batch_size' => 100,
                'batch_number' => 1
            ],
        ];
        $job = $this->getJob(1);
        $childJob1 = $this->getJob(2, $job);
        $childJob2 = $this->getJob(3, $job);
        $childJob = $this->getJob(10, $job);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(1, 'oro:import:processor_test:test_import:1')
            ->will(
                $this->returnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                    return $callback($jobRunner, $childJob);
                })
            );

        $jobRunner
            ->expects($this->at(0))
            ->method('createDelayed')
            ->with('oro:import:processor_test:test_import:1:chunk.1')
            ->will(
                $this->returnCallback(function ($jobId, $callback) use ($jobRunner, $childJob1) {
                    return $callback($jobRunner, $childJob1);
                })
            );

        $jobRunner
            ->expects($this->at(1))
            ->method('createDelayed')
            ->with('oro:import:processor_test:test_import:1:chunk.2')
            ->will(
                $this->returnCallback(function ($jobId, $callback) use ($jobRunner, $childJob2) {
                    return $callback($jobRunner, $childJob2);
                })
            );

        $messageData1 = $messageData;
        $messageData1['fileName'] = 'chunk_1_12345.csv';
        $messageData1['jobId'] = 2;
        $messageData2 = $messageData;
        $messageData2['fileName'] = 'chunk_2_12345.csv';
        $messageData2['jobId'] = 3;
        $messageData2['options']['batch_number'] = 2;

        $producer = $this->createMessageProducerInterfaceMock();
        $producer
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::HTTP_IMPORT, $messageData1],
                [Topics::HTTP_IMPORT, $messageData2]
            );

        $dependentContext = $this->createDependentJobContextMock();
        $dependentContext
            ->expects($this->once())
            ->method('addDependentJob')
            ->with(Topics::SEND_IMPORT_NOTIFICATION);

        $dependentJob = $this->createDependentJobMock();
        $dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentContext);

        $dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('12345.csv')
            ->willReturn('12345.csv');

        $fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with('12345.csv');

        $handler = $this->createHttpImportHandlerMock();
        $handler
            ->expects($this->once())
            ->method('splitImportFile')
            ->willReturn(['chunk_1_12345.csv', 'chunk_2_12345.csv'])
        ;
        $writer = $this->createWriterMock();
        $writerChain = new WriterChain();
        $writerChain->addWriter($writer, 'csv');

        $processor = new PreHttpImportMessageProcessor(
            $jobRunner,
            $producer,
            $this->createLoggerInterfaceMock(),
            $dependentJob,
            $fileManager,
            $handler,
            $writerChain,
            $this->createNotificationsettingsStub(),
            100
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($messageData));
        $message
            ->expects($this->once())
            ->method('getMessageId')
            ->willReturn(1);

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    protected function getJob($id, $rootJob = null)
    {
        $job = new Job();
        $job->setId($id);
        if ($rootJob instanceof Job) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }

    protected function getUser()
    {
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $organization = new Organization();
        $organization->setId(1);
        $user->setOrganization($organization);

        return $user;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|HttpImportHandler
     */
    protected function createHttpImportHandlerMock()
    {
        return $this->createMock(HttpImportHandler::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|WriterChain
     */
    protected function createWriterChainMock()
    {
        return $this->createMock(WriterChain::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    protected function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
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
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobService
     */
    protected function createDependentJobMock()
    {
        return $this->createMock(DependentJobService::class);
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
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobContext
     */
    private function createDependentJobContextMock()
    {
        return $this->createMock(DependentJobContext::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FileManager
     */
    private function createFileManagerMock()
    {
        return $this->createMock(FileManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FileStreamWriter
     */
    private function createWriterMock()
    {
        return $this->createMock(FileStreamWriter::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    private function createManagerRegistry()
    {
        return $this->createMock(ManagerRegistry::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|NotificationSettings
     */
    private function createNotificationsettingsStub()
    {
        $notificationSettings = $this->createMock(NotificationSettings::class);
        $notificationSettings
            ->expects($this->any())
            ->method('getSender')
            ->willReturn(From::emailAddress('sender_email@example.com', 'sender_name'));

        return $notificationSettings;
    }
}
