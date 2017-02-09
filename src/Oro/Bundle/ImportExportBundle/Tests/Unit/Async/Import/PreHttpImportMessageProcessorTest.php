<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor;

use Oro\Bundle\ImportExportBundle\Async\Topics;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Splitter\SplitterChain;
use Oro\Bundle\ImportExportBundle\Splitter\SplitterInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class PreparingHttpImportMessageProcessorTest
 * @package Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import
 */
class PreHttpImportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testImportProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->getSplitterChain(),
            $this->createDependentJobMock(),
            $this->createFileManagerMock()
        );

        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportMessageProcessor);
    }

    public function testImportProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [
            Topics::PRE_HTTP_IMPORT,
            Topics::IMPORT_HTTP_PREPARING,
            Topics::IMPORT_HTTP_VALIDATION_PREPARING
        ];
        $this->assertEquals($expectedSubscribedTopics, PreHttpImportMessageProcessor::getSubscribedTopics());
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. body: []')
        ;

        $processor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $this->getSplitterChain(),
            $this->createDependentJobMock(),
            $this->createFileManagerMock()
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn('[]')
        ;

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogWarningAndUseDefaultIfSlpitterNotFound()
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
        $csvSplitter = $this->createMock(SplitterInterface::class);
        $csvSplitter
            ->expects($this->at(0))
            ->method('getSplittedFilesNames')
            ->with('12345.test')
            ->willReturn(['12345.test']);
        $splitterChain = new SplitterChain();
        $splitterChain->addSplitter($csvSplitter, 'default');


        $processor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $logger,
            $splitterChain,
            $this->createDependentJobMock(),
            $fileManager
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
        $csvSplitter = $this->createMock(SplitterInterface::class);
        $csvSplitter
            ->expects($this->once())
            ->method('getSplittedFilesNames')
            ->with('12345.csv')
            ->willReturn(['12345.csv']);
        $splitterChain = new SplitterChain();
        $splitterChain->addSplitter($csvSplitter, 'csv');

        $processor = new PreHttpImportMessageProcessor(
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $splitterChain,
            $this->createDependentJobMock(),
            $fileManager
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
                        'options' => [],
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
                    ]))
        ;

        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('12345.csv')
            ->willReturn('12345.csv');

        $csvSplitter = $this->createMock(SplitterInterface::class);
        $csvSplitter
            ->expects($this->once())
            ->method('getSplittedFilesNames')
            ->with('12345.csv')
            ->willReturn([]);
        $csvSplitter
            ->expects($this->once())
            ->method('getErrors')
            ->willReturn(['test Error']);

        $splitterChain = new SplitterChain();
        $splitterChain->addSplitter($csvSplitter, 'csv');

        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('An error occurred while reading file test.csv: "test Error"');

        $producer = $this->createMessageProducerInterfaceMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(
                Topics::SEND_IMPORT_ERROR_NOTIFICATION,
                [
                    'file' => 'test.csv',
                    'error' => 'An error occurred while reading file test.csv: "test Error"',
                    'userId' => '1'
                ]
            );
        $processor = new PreHttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $producer,
            $logger,
            $splitterChain,
            $this->createDependentJobMock(),
            $fileManager
        );
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
            'options' => [],
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

        $csvSplitter = $this->createMock(SplitterInterface::class);
        $csvSplitter
            ->expects($this->once())
            ->method('getSplittedFilesNames')
            ->with('12345.csv')
            ->willReturn(['chunk_1_12345.csv', 'chunk_2_12345.csv']);

        $splitterChain = new SplitterChain();
        $splitterChain->addSplitter($csvSplitter, 'csv');


        $messageData1 = $messageData;
        $messageData1['fileName'] = 'chunk_1_12345.csv';
        $messageData1['jobId'] = 2;
        $messageData2 = $messageData;
        $messageData2['fileName'] = 'chunk_2_12345.csv';
        $messageData2['jobId'] = 3;

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

        $processor = new PreHttpImportMessageProcessor(
            $jobRunner,
            $producer,
            $this->createLoggerInterfaceMock(),
            $splitterChain,
            $dependentJob,
            $fileManager
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
     * @return \PHPUnit_Framework_MockObject_MockObject|HttpImportHandler
     */
    protected function createHttpImportHandlerMock()
    {
        return $this->createMock(HttpImportHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    protected function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|SplitterChain
     */
    protected function getSplitterChain()
    {
        $splitterChain = new SplitterChain();
        $splitterChain->addSplitter($this->createMock(SplitterInterface::class), 'csv');

        return $splitterChain;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DependentJobService
     */
    protected function createDependentJobMock()
    {
        return $this->createMock(DependentJobService::class);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|DependentJobContext
     */
    private function createDependentJobContextMock()
    {
        return $this->createMock(DependentJobContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileManager
     */
    private function createFileManagerMock()
    {
        return $this->createMock(FileManager::class);
    }
}
