<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\EventListener\JobListener;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Event\BeforeSaveJobEvent;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var AsyncOperationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $asyncOperationManager;

    /** @var JobListener */
    private $listener;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->asyncOperationManager = $this->createMock(AsyncOperationManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($this->em);

        $this->listener = new JobListener($doctrine, $this->asyncOperationManager);
    }

    public function testForNotRootJob()
    {
        $job = new Job();
        $job->setId(123);
        $job->setRootJob(new Job());

        $this->em->expects(self::never())
            ->method('find');
        $this->asyncOperationManager->expects(self::never())
            ->method('updateOperation');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForNewRootJob()
    {
        $job = new Job();

        $this->em->expects(self::never())
            ->method('find');
        $this->asyncOperationManager->expects(self::never())
            ->method('updateOperation');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobNotLinkedToAsyncOperation()
    {
        $job = new Job();
        $job->setId(123);

        $this->em->expects(self::never())
            ->method('find');
        $this->asyncOperationManager->expects(self::never())
            ->method('updateOperation');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToNotExistingAsyncOperation()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn(null);
        $this->asyncOperationManager->expects(self::never())
            ->method('updateOperation');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationButNoChanges()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_NEW);
        $job->setJobProgress(0);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame([], $callback());

                return false;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateProgress()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_RUNNING);
        $job->setJobProgress(0.1); // 10%

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        // now - 10 min
        ReflectionUtil::setPropertyValue(
            $operation,
            'createdAt',
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT10M'))
        );

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'progress' => 0.1
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateZeroProgress()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_RUNNING);
        $job->setJobProgress(0);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        // now - 10 min
        ReflectionUtil::setPropertyValue(
            $operation,
            'createdAt',
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT10M'))
        );
        $operation->setProgress(0.5);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'progress' => 0
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateInvalidProgress()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_RUNNING);
        $job->setJobProgress(-1);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        // now - 10 min
        ReflectionUtil::setPropertyValue(
            $operation,
            'createdAt',
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT10M'))
        );
        $operation->setProgress(0.5);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame([], $callback());

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateProgressWhenCreatedAtIsNotSet()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_RUNNING);
        $job->setJobProgress(0.1);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0.5);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'progress' => 0.1
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateJobId()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_RUNNING);

        $operation = new AsyncOperation();
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'jobId' => 123
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToSuccess()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_SUCCESS);

        $childJob1 = new Job();
        $childJob1->setData(['summary' => ['aggregateTime' => 10, 'readCount' => 1]]);
        $childJob2 = new Job();
        $childJob2->setData(['summary' => ['aggregateTime' => 15, 'readCount' => 2]]);
        $job->addChildJob($childJob1);
        $job->addChildJob($childJob2);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0.1);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'status'    => AsyncOperation::STATUS_SUCCESS,
                        'progress'  => 1,
                        'summary'   => [
                            'aggregateTime' => 25,
                            'readCount'     => 3,
                            'writeCount'    => 0,
                            'errorCount'    => 0,
                            'createCount'   => 0,
                            'updateCount'   => 0
                        ],
                        'hasErrors' => false
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToSuccessAndWithExistingSummary()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_SUCCESS);

        $childJob1 = new Job();
        $childJob1->setData(['summary' => ['aggregateTime' => 10, 'readCount' => 1]]);
        $childJob2 = new Job();
        $childJob2->setData(['summary' => ['aggregateTime' => 15, 'readCount' => 2]]);
        $job->addChildJob($childJob1);
        $job->addChildJob($childJob2);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0.1);
        $operation->setSummary([
            'aggregateTime' => 5,
            'readCount'     => 4,
            'writeCount'    => 3,
            'errorCount'    => 2,
            'createCount'   => 1,
            'updateCount'   => 6
        ]);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'status'    => AsyncOperation::STATUS_SUCCESS,
                        'progress'  => 1,
                        'summary'   => [
                            'aggregateTime' => 30,
                            'readCount'     => 7,
                            'writeCount'    => 3,
                            'errorCount'    => 2,
                            'createCount'   => 1,
                            'updateCount'   => 6
                        ],
                        'hasErrors' => true
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToFailed()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_FAILED);
        $job->setJobProgress(0.5);

        $childJob1 = new Job();
        $childJob1->setData(['summary' => ['readCount' => 2]]);
        $childJob2 = new Job();
        $childJob2->setData([
            'extra_chunk' => true,
            'summary'     => ['readCount' => 1, 'writeCount' => 1, 'createCount' => 1]
        ]);
        $childJob3 = new Job();
        $childJob3->setData([
            'extra_chunk' => true,
            'summary'     => ['readCount' => 1, 'errorCount' => 1]
        ]);
        $job->addChildJob($childJob1);
        $job->addChildJob($childJob2);
        $job->addChildJob($childJob3);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0.1);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'progress'  => 0.5,
                        'status'    => AsyncOperation::STATUS_FAILED,
                        'summary'   => [
                            'aggregateTime' => 0,
                            'readCount'     => 2,
                            'writeCount'    => 1,
                            'errorCount'    => 1,
                            'createCount'   => 1,
                            'updateCount'   => 0
                        ],
                        'hasErrors' => true
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToStale()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_STALE);
        $job->setJobProgress(0.5);

        $childJob1 = new Job();
        $childJob1->setData(['summary' => ['readCount' => 2]]);
        $job->addChildJob($childJob1);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0.1);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'progress'  => 0.5,
                        'status'    => AsyncOperation::STATUS_FAILED,
                        'summary'   => [
                            'aggregateTime' => 0,
                            'readCount'     => 2,
                            'writeCount'    => 0,
                            'errorCount'    => 0,
                            'createCount'   => 0,
                            'updateCount'   => 0,
                        ],
                        'hasErrors' => true
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToCancelled()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_CANCELLED);
        $job->setJobProgress(0.5);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0.1);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame(
                    [
                        'progress' => 0.5,
                        'status'   => AsyncOperation::STATUS_CANCELLED
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationWhenJobStatusChangedToFailedRedelivered()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_FAILED_REDELIVERED);
        $job->setJobProgress(0);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame([], $callback());

                return false;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationWhenJobStatusChangedToNew()
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_NEW);
        $job->setJobProgress(0);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);
        $operation->setProgress(0);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->asyncOperationManager->expects(self::once())
            ->method('updateOperation')
            ->with(1)
            ->willReturnCallback(function ($operationId, $callback) {
                self::assertSame([], $callback());

                return false;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }
}
