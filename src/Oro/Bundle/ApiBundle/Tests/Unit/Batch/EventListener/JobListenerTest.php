<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ApiBundle\Batch\EventListener\JobListener;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Event\BeforeSaveJobEvent;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UnitOfWork */
    private $uow;

    /** @var JobListener */
    private $listener;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->doctineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctineHelper
            ->expects(self::any())
            ->method('getEntityManager')
            ->with(AsyncOperation::class)
            ->willReturn($this->em);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->listener = new JobListener($this->doctineHelper);
    }

    public function testForNotRootJob()
    {
        $job = new Job();
        $job->setId(123);
        $job->setRootJob(new Job());

        $this->em->expects(self::never())
            ->method('find');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForNewRootJob()
    {
        $job = new Job();

        $this->em->expects(self::never())
            ->method('find');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));
    }

    public function testForRootJobNotLinkedToAsyncOperation()
    {
        $job = new Job();
        $job->setId(123);

        $this->em->expects(self::never())
            ->method('find');

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
        $this->uow->expects(self::never())
            ->method('recomputeSingleEntityChangeSet');

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
        $createdAtProperty = new \ReflectionProperty(AsyncOperation::class, 'createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue(
            $operation,
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT10M'))
        );

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(0.1, $operation->getProgress()); // 10%
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
        $createdAtProperty = new \ReflectionProperty(AsyncOperation::class, 'createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue(
            $operation,
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT10M'))
        );
        $operation->setProgress(0.5);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(0, $operation->getProgress());
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
        $createdAtProperty = new \ReflectionProperty(AsyncOperation::class, 'createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue(
            $operation,
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT10M'))
        );
        $operation->setProgress(0.5);

        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->uow->expects(self::never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(0.5, $operation->getProgress());
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

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(0.1, $operation->getProgress());
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

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(123, $operation->getJobId());
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

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(AsyncOperation::STATUS_SUCCESS, $operation->getStatus());
        self::assertSame(1, $operation->getProgress());
        self::assertEquals(
            [
                'aggregateTime' => 25,
                'readCount'     => 3,
                'writeCount'    => 0,
                'errorCount'    => 0,
                'createCount'   => 0,
                'updateCount'   => 0
            ],
            $operation->getSummary()
        );
        self::assertFalse($operation->isHasErrors());
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

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(AsyncOperation::STATUS_SUCCESS, $operation->getStatus());
        self::assertSame(1, $operation->getProgress());
        self::assertEquals(
            [
                'aggregateTime' => 30,
                'readCount'     => 7,
                'writeCount'    => 3,
                'errorCount'    => 2,
                'createCount'   => 1,
                'updateCount'   => 6
            ],
            $operation->getSummary()
        );
        self::assertTrue($operation->isHasErrors());
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

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(AsyncOperation::STATUS_FAILED, $operation->getStatus());
        self::assertSame(0.5, $operation->getProgress());
        self::assertEquals(
            [
                'aggregateTime' => 0,
                'readCount'     => 2,
                'writeCount'    => 1,
                'errorCount'    => 1,
                'createCount'   => 1,
                'updateCount'   => 0
            ],
            $operation->getSummary()
        );
        self::assertTrue($operation->isHasErrors());
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

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(AsyncOperation::STATUS_FAILED, $operation->getStatus());
        self::assertSame(0.5, $operation->getProgress());
        self::assertEquals(
            [
                'aggregateTime' => 0,
                'readCount'     => 2,
                'writeCount'    => 0,
                'errorCount'    => 0,
                'createCount'   => 0,
                'updateCount'   => 0
            ],
            $operation->getSummary()
        );
        self::assertTrue($operation->isHasErrors());
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

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, 1)
            ->willReturn($operation);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(AsyncOperation::class)
            ->willReturn($classMetadata);
        $this->uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::identicalTo($classMetadata), self::identicalTo($operation));

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(AsyncOperation::STATUS_CANCELLED, $operation->getStatus());
        self::assertSame(0.5, $operation->getProgress());
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
        $this->uow->expects(self::never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(AsyncOperation::STATUS_RUNNING, $operation->getStatus());
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
        $this->uow->expects(self::never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onBeforeSaveJob(new BeforeSaveJobEvent($job));

        self::assertSame(AsyncOperation::STATUS_RUNNING, $operation->getStatus());
    }
}
