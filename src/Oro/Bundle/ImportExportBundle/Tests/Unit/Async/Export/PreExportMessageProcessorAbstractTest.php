<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export\Stub\PreExportMessageProcessorStub;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PreExportMessageProcessorAbstractTest extends \PHPUnit\Framework\TestCase
{
    private const USER_ID = 54;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var DependentJobService|\PHPUnit\Framework\MockObject\MockObject */
    private $dependentJob;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ExportHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $exportHandler;

    /** @var PreExportMessageProcessorStub */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->exportHandler = $this->createMock(ExportHandler::class);
        $this->processor = new PreExportMessageProcessorStub(
            $this->jobRunner,
            $this->messageProducer,
            $this->tokenStorage,
            $this->dependentJob,
            $this->logger,
            $this->exportHandler,
            100
        );
    }

    public function testShouldRejectMessageIfGetMessageBodyReturnFalse(): void
    {
        $message = new Message();

        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorStub::REJECT, $result);
    }

    public function uniqueJobResultProvider(): array
    {
        return [
            [ true, PreExportMessageProcessorStub::ACK ],
            [ false, PreExportMessageProcessorStub::REJECT ],
        ];
    }

    /**
     * @dataProvider uniqueJobResultProvider
     * @param string $jobResult
     * @param string $expectedResult
     */
    public function testShouldReturnMessageStatusDependsOfJobResult($jobResult, $expectedResult): void
    {
        $jobUniqueName = 'job_unique_name';

        $message = new Message();
        $message->setMessageId(123);

        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturn($jobResult);

        $this->processor->setMessageBody(['message_body']);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals($expectedResult, $result);
    }

    public function testShouldThrowExceptionOnGetUserIfTokenIsNull(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Security token is null');

        $messageBody = ['message_body'];
        $jobUniqueName = 'job_unique_name';
        $message = new Message();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });

        $this->jobRunner
            ->expects($this->never())
            ->method('createDelayed');

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $dependentJobContext = $this->createDependentJobContextMock();

        $this->dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob
            ->expects($this->never())
            ->method('saveDependentJob');

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorStub::ACK, $result);
    }

    public function invalidUserTypeProvider(): array
    {
        $notObject = 'not_object';
        $notUserObject = new \stdClass();
        $userWithoutRequiredMethods = $this->createUserMock();
        $userWithoutGetEmailMethod = $this->createPartialMock(
            UserInterface::class,
            ['getId', 'getRoles', 'getPassword', 'getSalt', 'getUsername', 'eraseCredentials']
        );

        return [
            [$notObject],
            [$notUserObject],
            [$userWithoutRequiredMethods],
            [$userWithoutGetEmailMethod],
        ];
    }

    /**
     * @dataProvider invalidUserTypeProvider
     *
     * @param mixed $user
     */
    public function testShouldThrowExceptionOnGetUserIfUserTypeInvalid($user): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported user type');

        $messageBody = ['message_body'];
        $jobUniqueName = 'job_unique_name';
        $message = new Message();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });

        $this->jobRunner
            ->expects($this->never())
            ->method('createDelayed');

        $token = $this->createTokenMock();
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $dependentJobContext = $this->createDependentJobContextMock();

        $this->dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob
            ->expects($this->never())
            ->method('saveDependentJob');

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorStub::ACK, $result);
    }

    public function testShouldCreateDelayedJobAddDependentJobAndReturnACKOnEmptyExportResult(): void
    {
        $messageBody = [
            'jobName' => 'job_name',
            'exportType' => 'export_type',
            'outputFormat' => 'output_format',
            'entity' => 'Acme'
        ];
        $jobUniqueName = 'job_unique_name';
        $message = new Message();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });

        $this->jobRunner
            ->expects($this->once())
            ->method('createDelayed')
            ->with($jobUniqueName.'.chunk.1');

        $user = $this->createUserStub();
        $user
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::USER_ID);
        $user
            ->expects($this->once())
            ->method('getEmail');

        $token = $this->createTokenMock();
        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $dependentJobContext = $this->createDependentJobContextMock();
        $dependentJobContext
            ->expects($this->once())
            ->method('addDependentJob')
            ->with(
                Topics::POST_EXPORT,
                $this->callback(function ($message) {
                    $this->assertArrayHasKey('entity', $message);
                    $this->assertEquals('Acme', $message['entity']);

                    return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                })
            );

        $this->dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorStub::ACK, $result);
    }

    public function testShouldCreateTwoDelayedJobsAddDependentJobAndReturnACKOnTwoExportResultChunks(): void
    {
        $messageBody = [
            'jobName' => 'job_name',
            'exportType' => 'export_type',
            'outputFormat' => 'output_format',
            'entity' => 'Acme'
        ];
        $jobUniqueName = 'job_unique_name';
        $message = new Message();
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });
        $this->jobRunner
            ->expects($this->exactly(2))
            ->method('createDelayed')
            ->withConsecutive(
                [$jobUniqueName.'.chunk.1'],
                [$jobUniqueName.'.chunk.2']
            );

        $user = $this->createUserStub();
        $user
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::USER_ID);

        $user
            ->expects($this->once())
            ->method('getEmail');

        $token = $this->createTokenMock();
        $token
            ->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $dependentJobContext = $this->createDependentJobContextMock();
        $dependentJobContext
            ->expects($this->once())
            ->method('addDependentJob')
            ->with(
                Topics::POST_EXPORT,
                $this->callback(function ($message) {
                    $this->assertArrayHasKey('entity', $message);
                    $this->assertEquals('Acme', $message['entity']);

                    return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                })
            );

        $this->dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $this->processor->setExportingEntityIds(range(1, 101));
        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessorStub::ACK, $result);
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
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenInterface
     */
    private function createTokenMock()
    {
        return $this->createMock(TokenInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserInterface
     */
    private function createUserMock()
    {
        return $this->createMock(UserInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserInterface
     */
    private function createUserStub()
    {
        return $this->getMockBuilder(UserInterface::class)
            ->onlyMethods(['getRoles', 'getPassword', 'getSalt', 'getUsername', 'eraseCredentials'])
            ->addMethods(['getId', 'getEmail'])
            ->getMock();
    }

    /**
     * @param int $id
     * @param Job $rootJob
     *
     * @return Job
     */
    private function createJob($id, $rootJob = null)
    {
        $job = new Job();
        $job->setId($id);
        if ($rootJob instanceof Job) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }
}
