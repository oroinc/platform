<?php

namespace Oro\Component\MessageQueue\Tests\Unit\StatusCalculator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\StatusCalculator\CollectionCalculator;
use Oro\Component\MessageQueue\StatusCalculator\QueryCalculator;
use Oro\Component\MessageQueue\StatusCalculator\StatusCalculatorResolver;

class StatusCalculatorResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryCalculator|\PHPUnit\Framework\MockObject\MockObject */
    private $queryCalculator;

    /** @var CollectionCalculator|\PHPUnit\Framework\MockObject\MockObject */
    private $collectionCalculator;

    /** @var StatusCalculatorResolver */
    private $statusCalculatorResolver;

    protected function setUp(): void
    {
        $this->queryCalculator = $this->createMock(QueryCalculator::class);
        $this->collectionCalculator = $this->createMock(CollectionCalculator::class);

        $this->statusCalculatorResolver = new StatusCalculatorResolver(
            $this->collectionCalculator,
            $this->queryCalculator
        );
    }

    public function testGetQueryCalculatorForPersistentCollection()
    {
        $childJobCollection = new PersistentCollection(
            $this->createMock(EntityManager::class),
            $this->createMock(ClassMetadata::class),
            new ArrayCollection()
        );

        $rootJob = $this->getRootJobWithChildCollection($childJobCollection);
        $calculator = $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);

        $this->assertSame($this->queryCalculator, $calculator);
    }

    public function testGetCollectionCalculatorForArrayCollection()
    {
        $childJobCollection = new ArrayCollection();

        $rootJob = $this->getRootJobWithChildCollection($childJobCollection);
        $calculator = $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);

        $this->assertSame($this->collectionCalculator, $calculator);
    }

    public function testGetCalculatorForRootJobCollection()
    {
        $rootJob = $this->getRootJobWithChildCollection(new ArrayCollection());
        $calculator = $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);

        $this->assertSame($this->collectionCalculator, $calculator);
    }

    public function testGetCalculatorForRootJobIncorrectType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can\'t find status and progress calculator for this type of child jobs: "NULL".'
        );

        $rootJob = $this->getRootJobWithChildCollection(null);
        $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);
    }

    private function getRootJobWithChildCollection(?Collection $childJobCollection): Job
    {
        $rootJob = new Job();
        $rootJob->setChildJobs($childJobCollection);

        return $rootJob;
    }
}
