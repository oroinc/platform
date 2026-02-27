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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobListenerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AsyncOperationManager&MockObject $asyncOperationManager;
    private JobListener $listener;

    #[\Override]
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

    public function testForNotRootJob(): void
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

    public function testForNewRootJob(): void
    {
        $job = new Job();

        $this->em->expects(self::never())
            ->method('find');
        $this->asyncOperationManager->expects(self::never())
            ->method('updateOperation');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobNotLinkedToAsyncOperation(): void
    {
        $job = new Job();
        $job->setId(123);

        $this->em->expects(self::never())
            ->method('find');
        $this->asyncOperationManager->expects(self::never())
            ->method('updateOperation');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToNotExistingAsyncOperation(): void
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

    public function testForRootJobLinkedToAsyncOperationButNoChanges(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateProgress(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateZeroProgress(): void
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
                        'progress' => 0.0
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobLinkedToAsyncOperationUpdateInvalidProgress(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateProgressWhenCreatedAtIsNotSet(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateJobId(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToSuccess(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToSuccessAndWithExistingSummary(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToFailed(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToStale(): void
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

    public function testForRootJobLinkedToAsyncOperationUpdateStatusToCancelled(): void
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

    public function testForRootJobLinkedToAsyncOperationWhenJobStatusChangedToFailedRedelivered(): void
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

    public function testForRootJobLinkedToAsyncOperationWhenJobStatusChangedToNew(): void
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMergeAffectedEntities(): void
    {
        $job = new Job();
        $job->setId(123);
        $job->setData(['api_operation_id' => 1]);
        $job->setStatus(Job::STATUS_SUCCESS);

        $childJob1 = new Job();
        $childJob2 = new Job();
        $childJob2->setData([
            'affectedEntities' => [
                'primary'  => [[1, 'item1', false]],
                'included' => [['Test\Entity1', 1, 'include1', false]],
                'payload'  => [
                    'key1' => 'j2_val1',
                    'key2' => 2,
                    'key3' => ['k31' => 'j2_v31', 'k32' => 'j2_v32'],
                    'key4' => ['j2_v41', 'j2_v42'],
                    'key5' => ['j2_v5'],
                    'key6' => 'j2_v6',
                    'key7' => 'j2_v7',
                    'key9' => ['k91' => ['j2_v91'], 'k92' => ['j2_v92']]
                ]
            ]
        ]);
        $childJob3 = new Job();
        $childJob3->setData([
            'affectedEntities' => [
                'primary'  => [[2, 'item2', true]],
                'included' => [['Test\Entity1', 2, 'include2', true]],
                'payload'  => [
                    'key1' => 'j3_val1',
                    'key2' => 3,
                    'key3' => ['k31' => 'j3_v31', 'k33' => 'j2_v33'],
                    'key4' => ['j3_v41', 'j3_v42'],
                    'key5' => 'j3_v5',
                    'key6' => ['j3_v6'],
                    'key8' => 'j3_v8',
                    'key9' => ['k91' => ['j3_v91'], 'k93' => ['j2_v93']]
                ]
            ]
        ]);
        $childJob4 = new Job();
        $childJob4->setData([
            'affectedEntities' => [
                'primary' => [[3, 'item3', false]]
            ]
        ]);
        $job->addChildJob($childJob1);
        $job->addChildJob($childJob2);
        $job->addChildJob($childJob3);
        $job->addChildJob($childJob4);

        $operation = new AsyncOperation();
        $operation->setJobId(123);
        $operation->setStatus(AsyncOperation::STATUS_RUNNING);

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
                        'status'           => AsyncOperation::STATUS_SUCCESS,
                        'progress'         => 1,
                        'summary'          => [
                            'aggregateTime' => 0,
                            'readCount'     => 0,
                            'writeCount'    => 0,
                            'errorCount'    => 0,
                            'createCount'   => 0,
                            'updateCount'   => 0
                        ],
                        'hasErrors'        => false,
                        'affectedEntities' => [
                            'primary'  => [
                                [1, 'item1', false],
                                [2, 'item2', true],
                                [3, 'item3', false]
                            ],
                            'included' => [
                                ['Test\Entity1', 1, 'include1', false],
                                ['Test\Entity1', 2, 'include2', true]
                            ],
                            'payload'  => [
                                'key1' => 'j3_val1',
                                'key2' => 3,
                                'key3' => ['k31' => 'j3_v31', 'k32' => 'j2_v32', 'k33' => 'j2_v33'],
                                'key4' => ['j2_v41', 'j2_v42', 'j3_v41', 'j3_v42'],
                                'key5' => 'j3_v5',
                                'key6' => ['j3_v6'],
                                'key7' => 'j2_v7',
                                'key9' => ['k91' => ['j2_v91', 'j3_v91'], 'k92' => ['j2_v92'], 'k93' => ['j2_v93']],
                                'key8' => 'j3_v8'
                            ]
                        ]
                    ],
                    $callback()
                );

                return true;
            });

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }
}
