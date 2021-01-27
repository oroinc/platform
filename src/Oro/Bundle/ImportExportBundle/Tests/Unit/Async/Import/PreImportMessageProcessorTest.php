<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PreparingHttpImportMessageProcessorTest
 * @package Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import
 */
class PreImportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    private const USER_ID = 32;

    /**
     * @var JobRunner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var DependentJobService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dependentJob;

    /**
     * @var FileManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileManager;

    /**
     * @var ImportHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importHandler;

    /**
     * @var WriterChain|\PHPUnit_Framework_MockObject_MockObject
     */
    private $writerChain;

    /**
     * @var NotificationSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private $notificationSettings;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var PreImportMessageProcessor
     */
    private $preImportMessageProcessor;

    /**
     * @var FileStreamWriter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $writer;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->importHandler = $this->createMock(ImportHandler::class);
        $this->writerChain = $this->createMock(WriterChain::class);

        $this->notificationSettings = $this->createMock(NotificationSettings::class);
        $this->notificationSettings->expects($this->any())
            ->method('getSender')
            ->willReturn(From::emailAddress('sender_email@example.com', 'sender_name'));
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->writer = $this->createMock(FileStreamWriter::class);
        $writerChain = new WriterChain();
        $writerChain->addWriter($this->writer, 'csv');

        $this->preImportMessageProcessor = new PreImportMessageProcessor(
            $this->jobRunner,
            $this->messageProducer,
            $this->dependentJob,
            $this->fileManager,
            $this->importHandler,
            $writerChain,
            $this->notificationSettings,
            $this->registry,
            $this->eventDispatcher,
            100
        );

        $this->preImportMessageProcessor->setLogger($this->logger);
    }

    public function testImportProcessCanBeConstructedWithRequiredAttributes()
    {
        $this->assertInstanceOf(MessageProcessorInterface::class, $this->preImportMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $this->preImportMessageProcessor);
    }

    public function testImportProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [
            Topics::PRE_IMPORT,
            Topics::PRE_HTTP_IMPORT
        ];
        $this->assertEquals($expectedSubscribedTopics, PreImportMessageProcessor::getSubscribedTopics());
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn('[]');

        $result = $this->preImportMessageProcessor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogWarningAndUseDefaultIfSplitterNotFound()
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Not supported format: "test", using default');
        $this->fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.test')
            ->willReturn('12345.test');
        $this->fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with('123435.test');

        $this->importHandler
            ->expects($this->once())
            ->method('splitImportFile')
            ->willReturn(['test']);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $message = $this->createMessageMock();
        $message->expects($this->once())
            ->method('getMessageId')
            ->willReturn('1');

        $userId = 1;

        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'fileName' => '123435.test',
                'originFileName' => 'test.test',
                'userId' => $userId,
                'jobName' => 'test',
                'processorAlias' => 'test',
                'process' => 'import',
                'options' => [],
            ]));

        $result = $this->preImportMessageProcessor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunRunUniqueAndACKMessage()
    {
        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(1)
            ->willReturn(true);
        $this->fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.csv')
            ->willReturn('12345.csv');

        $options = [
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_DELIMITER => ';',
        ];

        $this->importHandler
            ->expects($this->once())
            ->method('setImportingFileName')
            ->with('12345.csv');
        $this->importHandler
            ->expects($this->once())
            ->method('setConfigurationOptions')
            ->with([
                Context::OPTION_ENCLOSURE => '|',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_BATCH_SIZE => 100,
            ]);
        $this->importHandler
            ->expects($this->once())
            ->method('splitImportFile')
            ->with('test', 'import', $this->writer)
            ->willReturn(['import_1.csv']);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

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
            ]));

        $message
            ->expects($this->once())
            ->method('getMessageId')
            ->willReturn('1');

        $result = $this->preImportMessageProcessor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testUniqueJobWithCustomName()
    {
        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->willReturnCallback(function ($jobId, $name) {
                self::assertEquals(1, $jobId);
                self::assertMatchesRegularExpression('/^oro:import:test:test:1:\d*/', $name);
                return true;
            });
        $this->fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.csv')
            ->willReturn('12345.csv');

        $options = [
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_DELIMITER => ';',
            'unique_job_slug' => 0,
        ];

        $this->importHandler
            ->expects($this->once())
            ->method('setImportingFileName')
            ->with('12345.csv');
        $this->importHandler
            ->expects($this->once())
            ->method('setConfigurationOptions')
            ->with([
                Context::OPTION_ENCLOSURE => '|',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_BATCH_SIZE => 100,
                'unique_job_slug' => 0,
            ]);
        $this->importHandler
            ->expects($this->once())
            ->method('splitImportFile')
            ->with('test', 'import', $this->writer)
            ->willReturn(['import_1.csv']);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

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
            ]));

        $message
            ->expects($this->once())
            ->method('getMessageId')
            ->willReturn('1');

        $result = $this->preImportMessageProcessor->process($message, $this->createSessionMock());
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

        $this->fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('12345.csv')
            ->willReturn('12345.csv');

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with('An error occurred while reading file test.csv: "test Error"');

        $expectedSender = From::emailAddress('sender_email@example.com', 'sender_name');
        $this->messageProducer
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

        $this->importHandler
            ->expects($this->once())
            ->method('splitImportFile')
            ->willThrowException(new \Exception('test Error'));

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

        $this->registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $result = $this->preImportMessageProcessor->process($message, $this->createSessionMock());

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

        $jobRunner = $this->jobRunner;
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->willReturnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                self::assertEquals(1, $jobId);
                self::assertMatchesRegularExpression('/^oro:import:processor_test:test_import:1:\d*/', $name);
                return $callback($jobRunner, $childJob);
            });

        $jobRunner
            ->expects($this->at(0))
            ->method('createDelayed')
            ->willReturnCallback(function ($jobId, $callback) use ($jobRunner, $childJob1) {
                self::assertMatchesRegularExpression('/^oro:import:processor_test:test_import:1:\d*:chunk.1/', $jobId);
                return $callback($jobRunner, $childJob1);
            });

        $jobRunner
            ->expects($this->at(1))
            ->method('createDelayed')
            ->willReturnCallback(function ($jobId, $callback) use ($jobRunner, $childJob2) {
                self::assertMatchesRegularExpression('/^oro:import:processor_test:test_import:1:\d*:chunk.2/', $jobId);
                return $callback($jobRunner, $childJob2);
            });

        $messageData1 = $messageData;
        $messageData1['fileName'] = 'chunk_1_12345.csv';
        $messageData1['jobId'] = 2;
        $messageData2 = $messageData;
        $messageData2['fileName'] = 'chunk_2_12345.csv';
        $messageData2['jobId'] = 3;
        $messageData2['options']['batch_number'] = 2;

        $this->messageProducer
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    Topics::IMPORT,
                    $this->callback(function ($messageData) use ($messageData1) {
                        $this->assertSame($messageData1['fileName'], $messageData['fileName']);
                        $this->assertSame($messageData1['jobId'], $messageData['jobId']);
                        $this->assertSame($messageData1['originFileName'], $messageData['originFileName']);
                        $this->assertSame($messageData1['userId'], $messageData['userId']);
                        $this->assertSame($messageData1['jobName'], $messageData['jobName']);
                        $this->assertSame($messageData1['processorAlias'], $messageData['processorAlias']);
                        $this->assertSame($messageData1['process'], $messageData['process']);
                        $this->assertSame(
                            $messageData1['options']['batch_size'],
                            $messageData['options']['batch_size']
                        );
                        $this->assertSame(
                            $messageData1['options']['batch_number'],
                            $messageData['options']['batch_number']
                        );

                        return true;
                    })
                ],
                [
                    Topics::IMPORT,
                    $this->callback(function ($messageData) use ($messageData2) {
                        $this->assertSame($messageData2['fileName'], $messageData['fileName']);
                        $this->assertSame($messageData2['jobId'], $messageData['jobId']);
                        $this->assertSame($messageData2['originFileName'], $messageData['originFileName']);
                        $this->assertSame($messageData2['userId'], $messageData['userId']);
                        $this->assertSame($messageData2['jobName'], $messageData['jobName']);
                        $this->assertSame($messageData2['processorAlias'], $messageData['processorAlias']);
                        $this->assertSame($messageData2['process'], $messageData['process']);
                        $this->assertSame(
                            $messageData2['options']['batch_size'],
                            $messageData['options']['batch_size']
                        );
                        $this->assertSame(
                            $messageData2['options']['batch_number'],
                            $messageData['options']['batch_number']
                        );

                        return true;
                    })
                ]
            );

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext
            ->expects($this->exactly(2))
            ->method('addDependentJob')
            ->withConsecutive([Topics::SEND_IMPORT_NOTIFICATION], [Topics::SAVE_IMPORT_EXPORT_RESULT]);

        $this->dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentContext);

        $this->dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('12345.csv')
            ->willReturn('12345.csv');

        $this->fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with('12345.csv');

        $this->importHandler
            ->expects($this->once())
            ->method('splitImportFile')
            ->willReturn(['chunk_1_12345.csv', 'chunk_2_12345.csv']);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function (BeforeImportChunksEvent $eventData) use ($messageData) {
                    $body = $eventData->getBody();

                    $this->assertSame($messageData['fileName'], $body['fileName']);
                    $this->assertSame($messageData['originFileName'], $body['originFileName']);
                    $this->assertSame($messageData['userId'], $body['userId']);
                    $this->assertSame($messageData['jobName'], $body['jobName']);
                    $this->assertSame($messageData['processorAlias'], $body['processorAlias']);
                    $this->assertSame($messageData['process'], $body['process']);
                    $this->assertSame($messageData['options']['batch_size'], $body['options']['batch_size']);
                    $this->assertSame($messageData['options']['batch_number'], $body['options']['batch_number']);

                    return true;
                }),
                Events::BEFORE_CREATING_IMPORT_CHUNK_JOBS
            );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($messageData));
        $message
            ->expects($this->once())
            ->method('getMessageId')
            ->willReturn('1');

        $result = $this->preImportMessageProcessor->process($message, $this->createSessionMock());
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
}
