<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PreImportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const USER_ID = 32;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private DependentJobService|\PHPUnit\Framework\MockObject\MockObject $dependentJob;

    private FileManager|\PHPUnit\Framework\MockObject\MockObject $fileManager;

    private ImportHandler|\PHPUnit\Framework\MockObject\MockObject $importHandler;

    private WriterChain|\PHPUnit\Framework\MockObject\MockObject $writerChain;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private FileStreamWriter|\PHPUnit\Framework\MockObject\MockObject $writer;

    private PreImportMessageProcessor $preImportMessageProcessor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->importHandler = $this->createMock(ImportHandler::class);
        $this->writerChain = $this->createMock(WriterChain::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->writer = $this->createMock(FileStreamWriter::class);

        $notificationSettings = $this->createMock(NotificationSettings::class);
        $notificationSettings->expects(self::any())
            ->method('getSender')
            ->willReturn(From::emailAddress('sender_email@example.com', 'sender_name'));

        $writerChain = new WriterChain();
        $writerChain->addWriter($this->writer, 'csv');

        $this->preImportMessageProcessor = new PreImportMessageProcessor(
            $this->jobRunner,
            $this->messageProducer,
            $this->dependentJob,
            $this->fileManager,
            $this->importHandler,
            $writerChain,
            $notificationSettings,
            $this->registry,
            $this->eventDispatcher,
            100
        );

        $this->preImportMessageProcessor->setLogger($this->logger);
    }

    public function testImportProcessCanBeConstructedWithRequiredAttributes(): void
    {
        self::assertInstanceOf(MessageProcessorInterface::class, $this->preImportMessageProcessor);
        self::assertInstanceOf(TopicSubscriberInterface::class, $this->preImportMessageProcessor);
    }

    public function testImportProcessShouldReturnSubscribedTopics(): void
    {
        self::assertEquals(
            [
                PreImportTopic::getName(),
            ],
            PreImportMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldLogWarningAndUseDefaultIfSplitterNotFound(): void
    {
        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Not supported format: "test", using default');

        $this->fileManager->expects(self::once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.test')
            ->willReturn('12345.test');
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('123435.test');

        $this->importHandler->expects(self::once())
            ->method('splitImportFile')
            ->willReturn(['test']);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $message = new Message();
        $message->setMessageId(1);
        $message->setBody([
            'fileName' => '123435.test',
            'originFileName' => 'test.test',
            'userId' => 1,
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => [],
        ]);

        $result = $this->preImportMessageProcessor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunRunUniqueAndACKMessage(): void
    {
        $options = [
            Context::OPTION_BATCH_SIZE => 100,
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_DELIMITER => ';',
        ];

        $message = new Message();
        $message->setMessageId(1);
        $message->setBody([
            'fileName' => '123435.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => $options,
        ]);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturn(true);

        $this->fileManager->expects(self::once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.csv')
            ->willReturn('12345.csv');

        $this->importHandler->expects(self::once())
            ->method('setImportingFileName')
            ->with('12345.csv');
        $this->importHandler->expects(self::once())
            ->method('setConfigurationOptions')
            ->with($options);
        $this->importHandler->expects(self::once())
            ->method('splitImportFile')
            ->with('test', 'import', $this->writer)
            ->willReturn(['import_1.csv']);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $result = $this->preImportMessageProcessor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testUniqueJobWithCustomName(): void
    {
        $options = [
            Context::OPTION_BATCH_SIZE => 100,
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_DELIMITER => ';',
            'unique_job_slug' => 0,
        ];

        $message = new Message();
        $message->setMessageId(1);
        $message->setBody([
            'fileName' => '123435.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => $options,
        ]);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(function ($actualMessage) use ($message) {
                $this->assertSame($actualMessage, $message);

                return true;
            });

        $this->fileManager->expects(self::once())
            ->method('writeToTmpLocalStorage')
            ->with('123435.csv')
            ->willReturn('12345.csv');

        $this->importHandler->expects(self::once())
            ->method('setImportingFileName')
            ->with('12345.csv');
        $this->importHandler->expects(self::once())
            ->method('setConfigurationOptions')
            ->with($options);
        $this->importHandler->expects(self::once())
            ->method('splitImportFile')
            ->with('test', 'import', $this->writer)
            ->willReturn(['import_1.csv']);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $result = $this->preImportMessageProcessor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectMessageAndSendErrorNotification(): void
    {
        $message = new Message();
        $message->setMessageId(1);
        $message->setBody([
            'fileName' => '12345.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => [],
        ]);

        $this->fileManager->expects(self::once())
            ->method('writeToTmpLocalStorage')
            ->with('12345.csv')
            ->willReturn('12345.csv');

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('An error occurred while reading file test.csv: "test Error"');

        $expectedSender = From::emailAddress('sender_email@example.com', 'sender_name');
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendEmailNotificationTemplateTopic::getName(),
                [
                    'from' => $expectedSender->toString(),
                    'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_ERROR,
                    'templateParams' => [
                        'originFileName' => 'test.csv',
                        'error' => 'The import file could not be imported due to a fatal error. ' .
                            'Please check its integrity and try again!',
                    ],
                    'recipientUserId' => self::USER_ID,
                ]
            );

        $this->importHandler->expects(self::once())
            ->method('splitImportFile')
            ->willThrowException(new \Exception('test Error'));

        $user = $this->getEntity(User::class, ['id' => self::USER_ID, 'email' => 'useremail@example.com']);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->willReturn($userRepository);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $result = $this->preImportMessageProcessor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldProcessPreparingMessageAndSendImportAndNotificationMessagesAndACKMessage(): void
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

        $message = new Message();
        $message->setMessageId(1);
        $message->setBody($messageData);

        $jobRunner = $this->jobRunner;
        $jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(function ($actualMessage, $callback) use ($message, $jobRunner, $childJob) {
                $this->assertSame($actualMessage, $message);

                return $callback($jobRunner, $childJob);
            });

        $jobRunner->expects(self::exactly(2))
            ->method('createDelayed')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($jobId, $callback) use ($jobRunner, $childJob1) {
                    self::assertEquals('job_name:chunk.1', $jobId);

                    return $callback($jobRunner, $childJob1);
                }),
                new ReturnCallback(function ($jobId, $callback) use ($jobRunner, $childJob2) {
                    self::assertEquals('job_name:chunk.2', $jobId);

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

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    ImportTopic::getName(),
                    $this->callback(function ($messageData) use ($messageData1) {
                        self::assertSame($messageData1['fileName'], $messageData['fileName']);
                        self::assertSame($messageData1['jobId'], $messageData['jobId']);
                        self::assertSame($messageData1['originFileName'], $messageData['originFileName']);
                        self::assertSame($messageData1['userId'], $messageData['userId']);
                        self::assertSame($messageData1['jobName'], $messageData['jobName']);
                        self::assertSame($messageData1['processorAlias'], $messageData['processorAlias']);
                        self::assertSame($messageData1['process'], $messageData['process']);
                        self::assertSame(
                            $messageData1['options']['batch_size'],
                            $messageData['options']['batch_size']
                        );
                        self::assertSame(
                            $messageData1['options']['batch_number'],
                            $messageData['options']['batch_number']
                        );

                        return true;
                    })
                ],
                [
                    ImportTopic::getName(),
                    $this->callback(function ($messageData) use ($messageData2) {
                        self::assertSame($messageData2['fileName'], $messageData['fileName']);
                        self::assertSame($messageData2['jobId'], $messageData['jobId']);
                        self::assertSame($messageData2['originFileName'], $messageData['originFileName']);
                        self::assertSame($messageData2['userId'], $messageData['userId']);
                        self::assertSame($messageData2['jobName'], $messageData['jobName']);
                        self::assertSame($messageData2['processorAlias'], $messageData['processorAlias']);
                        self::assertSame($messageData2['process'], $messageData['process']);
                        self::assertSame(
                            $messageData2['options']['batch_size'],
                            $messageData['options']['batch_size']
                        );
                        self::assertSame(
                            $messageData2['options']['batch_number'],
                            $messageData['options']['batch_number']
                        );

                        return true;
                    })
                ]
            );

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects(self::exactly(2))
            ->method('addDependentJob')
            ->withConsecutive([SendImportNotificationTopic::getName()], [SaveImportExportResultTopic::getName()]);

        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentContext);
        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->fileManager->expects(self::once())
            ->method('writeToTmpLocalStorage')
            ->with('12345.csv')
            ->willReturn('12345.csv');
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('12345.csv');

        $this->importHandler->expects(self::once())
            ->method('splitImportFile')
            ->willReturn(['chunk_1_12345.csv', 'chunk_2_12345.csv']);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                $this->callback(function (BeforeImportChunksEvent $eventData) use ($messageData) {
                    $body = $eventData->getBody();

                    self::assertSame($messageData['fileName'], $body['fileName']);
                    self::assertSame($messageData['originFileName'], $body['originFileName']);
                    self::assertSame($messageData['userId'], $body['userId']);
                    self::assertSame($messageData['jobName'], $body['jobName']);
                    self::assertSame($messageData['processorAlias'], $body['processorAlias']);
                    self::assertSame($messageData['process'], $body['process']);
                    self::assertSame($messageData['options']['batch_size'], $body['options']['batch_size']);
                    self::assertSame($messageData['options']['batch_number'], $body['options']['batch_number']);

                    return true;
                }),
                Events::BEFORE_CREATING_IMPORT_CHUNK_JOBS
            );

        $result = $this->preImportMessageProcessor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    private function getJob(int $id, Job $rootJob = null): Job
    {
        $job = new Job();
        $job->setId($id);
        $job->setName('job_name');
        if (null !== $rootJob) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }
}
