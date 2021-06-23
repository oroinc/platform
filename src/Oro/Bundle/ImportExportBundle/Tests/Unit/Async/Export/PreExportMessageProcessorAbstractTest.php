<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export\Stub\PreExportMessageProcessorStub;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\UserBundle\Entity\User;
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

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
     */
    public function testShouldReturnMessageStatusDependsOfJobResult(bool $jobResult, string $expectedResult): void
    {
        $jobUniqueName = 'job_unique_name';

        $message = new Message();
        $message->setMessageId(123);

        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturn($jobResult);

        $this->processor->setMessageBody(['message_body']);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

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

        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });

        $this->jobRunner->expects($this->never())
            ->method('createDelayed');

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $dependentJobContext = $this->createMock(DependentJobContext::class);

        $this->dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects($this->never())
            ->method('saveDependentJob');

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(PreExportMessageProcessorStub::ACK, $result);
    }

    /**
     * @return array
     */
    public function invalidUserTypeProvider(): array
    {
        $notObject = 'not_object';
        $notUserObject = new \stdClass();
        $userWithoutRequiredMethods = $this->createMock(UserInterface::class);
        $userWithoutGetEmailMethod = $this->createMock(UserInterface::class);

        return [
            [$notObject],
            [$notUserObject],
            [$userWithoutRequiredMethods],
            [$userWithoutGetEmailMethod],
        ];
    }

    /**
     * @dataProvider invalidUserTypeProvider
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

        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });

        $this->jobRunner->expects($this->never())
            ->method('createDelayed');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $dependentJobContext = $this->createMock(DependentJobContext::class);

        $this->dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects($this->never())
            ->method('saveDependentJob');

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

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

        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });

        $this->jobRunner->expects($this->once())
            ->method('createDelayed')
            ->with($jobUniqueName.'.chunk.1');

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(self::USER_ID);
        $user->expects($this->once())
            ->method('getEmail');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $dependentJobContext = $this->createMock(DependentJobContext::class);
        $dependentJobContext->expects($this->once())
            ->method('addDependentJob')
            ->with(
                Topics::POST_EXPORT,
                $this->callback(function ($message) {
                    $this->assertArrayHasKey('entity', $message);
                    $this->assertEquals('Acme', $message['entity']);

                    return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                })
            );

        $this->dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

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

        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobUniqueName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });
        $this->jobRunner->expects($this->exactly(2))
            ->method('createDelayed')
            ->withConsecutive(
                [$jobUniqueName.'.chunk.1'],
                [$jobUniqueName.'.chunk.2']
            );

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(self::USER_ID);
        $user->expects($this->once())
            ->method('getEmail');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $dependentJobContext = $this->createMock(DependentJobContext::class);
        $dependentJobContext->expects($this->once())
            ->method('addDependentJob')
            ->with(
                Topics::POST_EXPORT,
                $this->callback(function ($message) {
                    $this->assertArrayHasKey('entity', $message);
                    $this->assertEquals('Acme', $message['entity']);

                    return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                })
            );

        $this->dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->processor->setMessageBody($messageBody);
        $this->processor->setJobUniqueName($jobUniqueName);
        $this->processor->setExportingEntityIds(range(1, 101));
        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(PreExportMessageProcessorStub::ACK, $result);
    }

    private function createJob(int $id, Job $rootJob = null): Job
    {
        $job = new Job();
        $job->setId($id);
        if ($rootJob instanceof Job) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }
}
