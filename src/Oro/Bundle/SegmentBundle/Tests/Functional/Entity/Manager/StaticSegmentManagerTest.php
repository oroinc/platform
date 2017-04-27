<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Manager;

use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntityData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class StaticSegmentManagerTest extends WebTestCase
{
    /** @var StaticSegmentManager */
    private $manager;

    /** @var SegmentSnapshotRepository */
    private $snapshotRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowAwareEntityData::class, LoadSegmentData::class]);

        $this->manager = $this->getContainer()->get('oro_segment.static_segment_manager');
        $this->snapshotRepository = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(SegmentSnapshot::class);
    }

    /**
     * @param int $expectedNumber
     */
    private function assertSnapshotsCount($expectedNumber)
    {
        $resultNumber = $this->snapshotRepository->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($expectedNumber, $resultNumber);
    }

    public function testRunWithoutLimit()
    {
        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $this->manager->run($staticSegment);
        $this->assertSnapshotsCount(50);
        $this->assertNotNull($staticSegment->getLastRun());
    }

    public function testRunWithLimit()
    {
        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);
        $staticSegment->setRecordsLimit(15);

        $this->manager->run($staticSegment);
        $this->assertSnapshotsCount(15);
        $this->assertNotNull($staticSegment->getLastRun());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Only static segments could have snapshots.
     */
    public function testOnlyStaticSegmentsException()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $this->manager->run($dynamicSegment);
    }
}
