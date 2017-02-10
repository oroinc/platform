<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HttpImportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testImportProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new HttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineMock(),
            $this->createTokenStorageInterfaceMock(),
            $this->createImportExportResultSummarizerMock(),
            $this->createJobStorageMock(),
            $this->createLoggerInterfaceMock(),
            $this->createFileManagerMock()
        );
        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportMessageProcessor);
    }

    public function testImportProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::HTTP_IMPORT, Topics::IMPORT_HTTP, Topics::IMPORT_HTTP_VALIDATION,];
        $this->assertEquals($expectedSubscribedTopics, HttpImportMessageProcessor::getSubscribedTopics());
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. body: []')
        ;

        $processor = new HttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineMock(),
            $this->createTokenStorageInterfaceMock(),
            $this->createImportExportResultSummarizerMock(),
            $this->createJobStorageMock(),
            $logger,
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

    public function testShouldLogErrorAndRejectMessageIfUserNotFound()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('User not found. id: 1')
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
        $processor = new HttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $doctrine,
            $this->createTokenStorageInterfaceMock(),
            $this->createImportExportResultSummarizerMock(),
            $this->createJobStorageMock(),
            $logger,
            $this->createFileManagerMock()
        );
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                    'fileName' => '123456.csv',
                    'originFileName' => 'test.csv',
                    'userId' => '1',
                    'jobId' => '1',
                    'jobName' => 'test_import',
                    'processorAlias' => 'test',
                    'process' => 'import',
                    'options' => [],
                ]))
        ;
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunJobACKMessage()
    {
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');

        $userRepo = $this->createUserRepositoryMock();
        $userRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($userRepo))
        ;

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->willReturn(true)
        ;
        $processor = new HttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $doctrine,
            $this->createTokenStorageInterfaceMock(),
            $this->createImportExportResultSummarizerMock(),
            $this->createJobStorageMock(),
            $this->createLoggerInterfaceMock(),
            $this->createFileManagerMock()
        )
        ;
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode([
                    'fileName' => '123456.csv',
                    'originFileName' => 'test.csv',
                    'userId' => '1',
                    'jobId' => '1',
                    'jobName' => 'test',
                    'processorAlias' => 'test',
                    'process' => 'import',
                    'options' => [],
                ])
            )
        ;
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldProcessedDataAndACKMessage()
    {
        $job = new Job();
        $job->setId(1);
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');
        $organization = new Organization();
        $organization->setId(1);
        $user->setOrganization($organization);

        $userRepo = $this->createUserRepositoryMock();
        $userRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);
        ;
        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($userRepo))
        ;
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
        $logger = $this->createLoggerInterfaceMock();
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
            ->willReturn(
                [
                    'success' => true,
                    'filePath' => 'csv',
                    'importInfo' => 'imports was done',
                    'message' => '',
                ]
            );
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
            ->method('getSummaryMessage')
            ->with()
            ->willReturn('Import of the csv is completed, success: 1, info: imports was done, message: ');
        ;

        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->once())
            ->method('writeToTmpLocalStorage')
            ->with('123456.csv')
            ->willReturn('123456.csv');

        $processor = new HttpImportMessageProcessor(
            $httpImportHandler,
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $doctrine,
            $this->createTokenStorageInterfaceMock(),
            $importExportResultSummarizer,
            $jobStorage,
            $logger,
            $fileManager
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode(
                    [
                        'fileName' => '123456.csv',
                        'originFileName' => 'test.csv',
                        'userId' => '1',
                        'jobId' => '1',
                        'jobName' => 'test',
                        'processorAlias' => 'test',
                        'process' => 'import',
                        'options' => [],
                    ]
                )
            )
        ;
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
}
