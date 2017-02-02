<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Import;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

use Oro\Bundle\ImportExportBundle\Async\Import\AbstractChunkImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportValidationMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;

class ChunkHttpImportAndValidationMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testImportProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportMessageProcessor = new ChunkHttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineMock(),
            $this->createTokenStorageInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createJobStorageMock()
        );

        $this->assertInstanceOf(AbstractChunkImportMessageProcessor::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportMessageProcessor);
    }

    public function testImportProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::IMPORT_HTTP,];
        $this->assertEquals($expectedSubscribedTopics, ChunkHttpImportMessageProcessor::getSubscribedTopics());
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. body: []')
        ;

        $processor = new ChunkHttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createJobStorageMock()
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

        $processor = new ChunkHttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $doctrine,
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createJobStorageMock()
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                    'filePath' => 'test.csv',
                    'userId' => '1',
                    'jobId' => '1',
                    'jobName' => 'test',
                    'processorAlias' => 'test',
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

        $processor = new ChunkHttpImportMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $doctrine,
            $this->createTokenStorageInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createJobStorageMock()
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode([
                    'filePath' => 'test.csv',
                    'userId' => '1',
                    'jobId' => '1',
                    'jobName' => 'test',
                    'processorAlias' => 'test',
                    'options' => [],
                ])
            )
        ;
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

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
            );

        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Import of the csv is completed, success: 1, info: imports was done, message: ');

        $httpImportHandler = $this->createHttpImportHandlerMock();
        $httpImportHandler
            ->expects($this->once())
            ->method('setImportingFileName')
            ->with('test.csv')
        ;
        $httpImportHandler
            ->expects($this->once())
            ->method('handleImport')
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
            ->method('saveJob');
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn($job);
        $processor = new ChunkHttpImportMessageProcessor(
            $httpImportHandler,
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $doctrine,
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $jobStorage
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode(
                    [
                        'filePath' => 'test.csv',
                        'userId' => '1',
                        'jobId' => '1',
                        'jobName' => 'test',
                        'processorAlias' => 'test',
                        'options' => [],
                    ]
                )
            );
        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testValidationProcessCanBeConstructedWithRequiredAttributes()
    {
        $chunkHttpImportValidationMessageProcessor = new ChunkHttpImportValidationMessageProcessor(
            $this->createHttpImportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineMock(),
            $this->createTokenStorageInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createJobStorageMock()
        );

        $this->assertInstanceOf(AbstractChunkImportMessageProcessor::class, $chunkHttpImportValidationMessageProcessor);
        $this->assertInstanceOf(MessageProcessorInterface::class, $chunkHttpImportValidationMessageProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $chunkHttpImportValidationMessageProcessor);
    }

    public function testValidationProcessShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::IMPORT_HTTP_VALIDATION,];
        $this->assertEquals(
            $expectedSubscribedTopics,
            ChunkHttpImportValidationMessageProcessor::getSubscribedTopics()
        );
    }


    public function testValidationProcessShouldProcessedDataAndACKMessage()
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

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($userRepo));
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->will(
                $this->returnCallback(function ($jobId, $callback) use ($jobRunner, $job) {
                        return $callback($jobRunner, $job);
                })
            );
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Import validation of the csv from Test is completed.
                 Success: true.
                 Info: "[]".
                 Errors: "[]"');
        $httpImportHandler = $this->createHttpImportHandlerMock();
        $httpImportHandler
            ->expects($this->once())
            ->method('setImportingFileName')
            ->with('test.csv');
        $httpImportHandler
            ->expects($this->once())
            ->method('handleImportValidation')
            ->willReturn(
                [
                    'success' => true,
                    'filePath' => 'csv',
                    'importInfo' => 'imports was done',
                    'message' => '',
                    'entityName' => 'Test',
                    'counts' => '[]',
                    'errors' => '[]',
                ]
            );
        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob');
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->with(1)
            ->willReturn($job);

        $processor = new ChunkHttpImportValidationMessageProcessor(
            $httpImportHandler,
            $jobRunner,
            $this->createMessageProducerInterfaceMock(),
            $doctrine,
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $jobStorage
        );
        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode(
                    [
                        'filePath' => 'test.csv',
                        'userId' => '1',
                        'jobId' => '1',
                        'jobName' => 'test',
                        'processorAlias' => 'test',
                        'options' => [],
                    ]
                )
            );

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
}
