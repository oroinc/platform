<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\UniqueJobHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class JobStorageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var JobStorage */
    private $storage;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        /** @var UniqueJobHandler $uniqueJobHandler */
        $uniqueJobHandler = $this->createMock(UniqueJobHandler::class);

        $this->storage = new JobStorage($this->doctrine, Job::class, $uniqueJobHandler);
    }

    public function testCreateJob(): void
    {
        $job = $this->storage->createJob();

        $this->assertEquals(Job::class, get_class($job));
    }

    public function testCreateJobQueryBuilder(): void
    {
        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $qb = new QueryBuilder($em);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Job::class)
            ->willReturn($em);

        $result = $this->storage->createJobQueryBuilder('e');

        $this->assertEquals(
            sprintf('SELECT e FROM %s e', Job::class),
            $result->getDQL()
        );
    }
}
