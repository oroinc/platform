<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Gaufrette\Filesystem;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Handler\PostponedRowsHandler;
use Oro\Bundle\ImportExportBundle\Async\Import\ImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class HttpImportMessageProcessorTest extends \PHPUnit_Framework_TestCase
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
            $this->createMock(PostponedRowsHandler::class),
            $this->createTokenSerializerMock(),
            $this->createTokenStorageInterfaceMock()
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
            $this->createMock(PostponedRowsHandler::class),
            $this->createTokenSerializerMock(),
            $this->createTokenStorageInterfaceMock()
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn('[]');

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogErrorAndRejectMessageIfTokenCannotBeSet()
    {
        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('security_token'))
            ->willReturn(null);

        $tokenStorage = $this->createTokenStorageInterfaceMock();
        $tokenStorage
            ->expects($this->never())
            ->method('setToken');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical');

        $job = new Job();
        $job->setId(1);
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
            );

        $processor = new HttpImportMessageProcessor(
            $jobRunner,
            $this->createImportExportResultSummarizerMock(),
            $this->createJobStorageMock(),
            $logger,
            $this->createFileManagerMock(),
            $this->createHttpImportHandlerMock(),
            $this->createMock(PostponedRowsHandler::class),
            $tokenSerializer,
            $tokenStorage
        );

        $message = new NullMessage();
        $message->setBody(
            json_encode(
                [
                    'fileName' => '123456.csv',
                    'originFileName' => 'test.csv',
                    'userId' => '1',
                    'securityToken' => 'security_token',
                    'jobId' => '1',
                    'jobName' => 'test',
                    'processorAlias' => 'test',
                    'process' => 'import',
                    'options' => [],
                ]
            )
        );

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

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('security_token'))
            ->willReturn($token)
        ;

        $tokenStorage = $this->createTokenStorageInterfaceMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $processor = new HttpImportMessageProcessor(
            $jobRunner,
            $importExportResultSummarizer,
            $jobStorage,
            $logger,
            $fileManager,
            $httpImportHandler,
            $this->createMock(PostponedRowsHandler::class),
            $tokenSerializer,
            $tokenStorage
        );

        $message = new NullMessage();
        $message->setBody(json_encode([
            'fileName' => '123456.csv',
            'originFileName' => 'test.csv',
            'userId' => '1',
            'securityToken' => 'security_token',
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
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected function createTokenStorageInterfaceMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
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
     * @return \PHPUnit_Framework_MockObject_MockObject|ImportExportResultSummarizer
     */
    private function createImportExportResultSummarizerMock()
    {
        return $this->createMock(ImportExportResultSummarizer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileManager
     */
    private function createFileManagerMock()
    {
        return $this->createMock(FileManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenSerializerInterface
     */
    private function createTokenSerializerMock()
    {
        return $this->createMock(TokenSerializerInterface::class);
    }
}
