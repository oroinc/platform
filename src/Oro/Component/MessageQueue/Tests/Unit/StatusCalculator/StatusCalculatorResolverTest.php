<?php

namespace Oro\Component\MessageQueue\Tests\Unit\StatusCalculator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\StatusCalculator\CollectionCalculator;
use Oro\Component\MessageQueue\StatusCalculator\QueryCalculator;
use Oro\Component\MessageQueue\StatusCalculator\StatusCalculatorResolver;

class StatusCalculatorResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StatusCalculatorResolver
     */
    private $statusCalculatorResolver;

    /**
     * @var QueryCalculator | \PHPUnit_Framework_MockObject_MockObject
     */
    private $queryCalculator;

    /**
     * @var CollectionCalculator | \PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionCalculator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->queryCalculator = $this->createMock(QueryCalculator::class);
        $this->collectionCalculator = $this->createMock(CollectionCalculator::class);

        $this->statusCalculatorResolver = new StatusCalculatorResolver(
            $this->collectionCalculator,
            $this->queryCalculator
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->queryCalculator);
        unset($this->collectionCalculator);
        unset($this->statusCalculatorResolver);
    }

    public function testGetCalculatorForRootJobPersistentCollectionInitialized()
    {
        $childJobCollection = new PersistentCollection(
            $this->createMock(EntityManager::class),
            Job::class,
            new ArrayCollection()
        );

        $rootJob = $this->getRootJobWithChildCollection($childJobCollection);
        $calculator = $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);

        $this->assertSame($this->collectionCalculator, $calculator);
    }

    public function testGetCalculatorForRootJobPersistentCollectionIsNotInitialized()
    {
        $childJobCollection = new PersistentCollection(
            $this->createMock(EntityManager::class),
            Job::class,
            new ArrayCollection()
        );
        $childJobCollection->setInitialized(false);

        $rootJob = $this->getRootJobWithChildCollection($childJobCollection);
        $calculator = $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);

        $this->assertSame($this->queryCalculator, $calculator);
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

    /**
     * @param Collection $childJobCollection
     *
     * @return Job
     */
    private function getRootJobWithChildCollection(Collection $childJobCollection = null)
    {
        $rootJob = new Job();
        $rootJob->setChildJobs($childJobCollection);

        return $rootJob;
    }
}
