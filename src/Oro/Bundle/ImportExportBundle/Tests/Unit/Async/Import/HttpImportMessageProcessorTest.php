<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Gaufrette\Filesystem;
use Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Import\ImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\PostponedRowsHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class HttpImportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testImportProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new HttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createImportExportResultSummarizerMock(),
            $this->createJobStorageMock(),
            $this->createLoggerMock(),
            $this->createFileManagerMock(),
            $this->createHttpImportHandlerMock(),
            $this->createMock(PostponedRowsHandler::class)
        );
        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(ImportMessageProcessor::class, $chunkHttpImportMessageProcessor);
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new HttpImportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createImportExportResultSummarizerMock(),
            $this->createJobStorageMock(),
            $logger,
            $this->createFileManagerMock(),
            $this->createHttpImportHandlerMock(),
            $this->createMock(PostponedRowsHandler::class)
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn('[]');

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function dataProviderForTestProcessImport()
    {
        return [
            [
                [
                    'success' => true,
                    'filePath' => 'csv',
                    'importInfo' => 'imports was done',
                    'message' => '',
                ],
                0
            ],
            [
                [
                    'success' => true,
                    'filePath' => 'csv',
                    'importInfo' => 'imports was done',
                    'message' => '',
                    'errors' => ['test error'],
                ],
                1
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForTestProcessImport
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldProcessedDataMessage($body, $writeLog)
    {
        $job = new Job();
        $job->setId(1);
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $organization = new Organization();
        $organization->setId(1);
        $user->setOrganization($organization);
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->will(
                $this->returnCallback(
                    function ($jobId, $callback) use ($jobRunner, $job) {
                        return $callback($jobRunner, $job);
                    }
                )
            )
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Import of the csv is completed, success: 1, info: imports was done, message: ')
        ;

        $httpImportHandler = $this->createHttpImportHandlerMock();
        $httpImportHandler
            ->expects($this->once())
            ->method('setImportingFileName')
            ->with('123456.csv')
        ;
        $httpImportHandler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($body);

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ;
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn($job);
        ;

        $importExportResultSummarizer = $this->createImportExportResultSummarizerMock();
        $importExportResultSummarizer
            ->expects($this->once())
            ->method('getImportSummaryMessage')
            ->with()
            ->willReturn('Import of the csv is completed, success: 1, info: imports was done, message: ');
        ;

        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123456.csv')
            ->willReturn('123456.csv');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->exactly($writeLog))
            ->method('write');

        $fileManager
            ->expects($this->exactly($writeLog))
            ->method('getFileSystem')
            ->willReturn($filesystem);

        $processor = new HttpImportMessageProcessor(
            $jobRunner,
            $importExportResultSummarizer,
            $jobStorage,
            $logger,
            $fileManager,
            $httpImportHandler,
            $this->createMock(PostponedRowsHandler::class)
        );

        $message = new NullMessage();
        $message->setBody(json_encode([
            'fileName' => '123456.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'jobId' => '1',
            'jobName' => 'test',
            'processorAlias' => 'test',
            'process' => 'import',
            'options' => [],
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|HttpImportHandler
     */
    protected function createHttpImportHandlerMock()
    {
        return $this->createMock(HttpImportHandler::class);
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
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected function createLoggerMock()
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
     * @return \PHPUnit\Framework\MockObject\MockObject|ImportExportResultSummarizer
     */
    private function createImportExportResultSummarizerMock()
    {
        return $this->createMock(ImportExportResultSummarizer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FileManager
     */
    private function createFileManagerMock()
    {
        return $this->createMock(FileManager::class);
    }
}
