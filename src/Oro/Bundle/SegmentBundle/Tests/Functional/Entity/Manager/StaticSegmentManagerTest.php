<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Manager;

use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntityData;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
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

    public function testRunWithEntities()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);

        $this->manager->run($segment);
        $this->assertSnapshotsCount(5);

        // Modify existing entities names
        /** @var WorkflowAwareEntity $removedWorkflowAwareEntity */
        $removedWorkflowAwareEntity = $this->getReference('workflow_aware_entity_10');
        $removedWorkflowAwareEntity->setName('test');

        /** @var WorkflowAwareEntity $addedWorkflowAwareEntity */
        $addedWorkflowAwareEntity = $this->getReference('workflow_aware_entity_5');
        $addedWorkflowAwareEntity->setName('entity_90');

        $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class)->flush();

        // Run partial actualization for modified entities
        $this->manager->run($segment, [$addedWorkflowAwareEntity->getId(), $removedWorkflowAwareEntity->getId()]);

        $actualSegmentSnapshotIds = array_map(
            function (SegmentSnapshot $snapshot) {
                return $snapshot->getIntegerEntityId();
            },
            $this->snapshotRepository->findBy(['segment' => $segment])
        );

        $this->assertSnapshotsCount(5);
        $this->assertContains($addedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);
        $this->assertNotContains($removedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);

        $this->assertNotNull($segment->getLastRun());
    }

    public function testPartialActualization()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);

        $this->manager->run($segment);
        $this->assertSnapshotsCount(5);

        // Modify existing entities names
        /** @var WorkflowAwareEntity $removedWorkflowAwareEntity */
        $removedWorkflowAwareEntity = $this->getReference('workflow_aware_entity_10');
        $removedWorkflowAwareEntity->setName('test');

        /** @var WorkflowAwareEntity $addedWorkflowAwareEntity */
        $addedWorkflowAwareEntity = $this->getReference('workflow_aware_entity_5');
        $addedWorkflowAwareEntity->setName('entity_90');

        $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class)->flush();

        // Run partial actualization for added entity only
        $this->manager->run($segment, [$addedWorkflowAwareEntity->getId()]);

        $actualSegmentSnapshotIds = array_map(
            function (SegmentSnapshot $snapshot) {
                return $snapshot->getIntegerEntityId();
            },
            $this->snapshotRepository->findBy(['segment' => $segment])
        );

        $this->assertSnapshotsCount(6);
        $this->assertContains($addedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);
        // This entity should remain in the snapshot as it wasn't actualized
        $this->assertContains($removedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);

        $this->assertNotNull($segment->getLastRun());
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
        $staticSegment->setRecordsLimit(2);

        $this->manager->run($staticSegment);
        $this->assertSnapshotsCount(2);
        $this->assertNotNull($staticSegment->getLastRun());
    }

    public function testRunWithSegmentFilter()
    {

        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);
        $this->manager->run($staticSegment);

        /** @var Segment $staticSegmentWithFilter */
        $staticSegmentWithFilter = $this->getReference(LoadSegmentData::SEGMENT_STATIC_WITH_SEGMENT_FILTER);
        $staticSegmentWithFilter->setRecordsLimit(7);

        $this->manager->run($staticSegmentWithFilter);
        $this->assertSnapshotsCount(57);
        $this->assertNotNull($staticSegment->getLastRun());
    }
}
