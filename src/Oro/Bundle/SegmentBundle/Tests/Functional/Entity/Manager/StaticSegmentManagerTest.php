<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
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

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowAwareEntityData::class, LoadSegmentData::class]);

        $this->manager = self::getContainer()->get('oro_segment.static_segment_manager');
    }

    private function getSegment(string $reference): Segment
    {
        return $this->getReference($reference);
    }

    private function getWorkflowAwareEntity(string $reference): WorkflowAwareEntity
    {
        return $this->getReference($reference);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
    }

    private function getSegmentSnapshotRepository(): SegmentSnapshotRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(SegmentSnapshot::class);
    }

    public function testRunWithEntities()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);

        $this->manager->run($segment);
        $this->assertSnapshotsCount(5);

        // Modify existing entities names
        $removedWorkflowAwareEntity = $this->getWorkflowAwareEntity('workflow_aware_entity_10');
        $removedWorkflowAwareEntity->setName('test');

        $addedWorkflowAwareEntity = $this->getWorkflowAwareEntity('workflow_aware_entity_5');
        $addedWorkflowAwareEntity->setName('entity_90');

        $this->getEntityManager()->flush();

        // Run partial actualization for modified entities
        $this->manager->run($segment, [$addedWorkflowAwareEntity->getId(), $removedWorkflowAwareEntity->getId()]);

        $actualSegmentSnapshotIds = array_map(
            function (SegmentSnapshot $snapshot) {
                return $snapshot->getIntegerEntityId();
            },
            $this->getSegmentSnapshotRepository()->findBy(['segment' => $segment])
        );

        $this->assertSnapshotsCount(5);
        self::assertContains($addedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);
        self::assertNotContains($removedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);

        self::assertNotNull($segment->getLastRun());
    }

    public function testPartialActualization()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);

        $this->manager->run($segment);
        $this->assertSnapshotsCount(5);

        // Modify existing entities names
        $removedWorkflowAwareEntity = $this->getWorkflowAwareEntity('workflow_aware_entity_10');
        $removedWorkflowAwareEntity->setName('test');

        $addedWorkflowAwareEntity = $this->getWorkflowAwareEntity('workflow_aware_entity_5');
        $addedWorkflowAwareEntity->setName('entity_90');

        $this->getEntityManager()->flush();

        // Run partial actualization for added entity only
        $this->manager->run($segment, [$addedWorkflowAwareEntity->getId()]);

        $actualSegmentSnapshotIds = array_map(
            function (SegmentSnapshot $snapshot) {
                return $snapshot->getIntegerEntityId();
            },
            $this->getSegmentSnapshotRepository()->findBy(['segment' => $segment])
        );

        $this->assertSnapshotsCount(6);
        self::assertContains($addedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);
        // This entity should remain in the snapshot as it wasn't actualized
        self::assertContains($removedWorkflowAwareEntity->getId(), $actualSegmentSnapshotIds);

        self::assertNotNull($segment->getLastRun());
    }

    private function assertSnapshotsCount(int $expectedNumber): void
    {
        $resultNumber = $this->getSegmentSnapshotRepository()->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->getQuery()
            ->getSingleScalarResult();

        self::assertEquals($expectedNumber, $resultNumber);
    }

    public function testRunWithoutLimit()
    {
        $staticSegment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);

        $this->manager->run($staticSegment);
        $this->assertSnapshotsCount(50);
        self::assertNotNull($staticSegment->getLastRun());
    }

    public function testRunWithLimit()
    {
        $staticSegment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);
        $staticSegment->setRecordsLimit(2);

        $this->manager->run($staticSegment);
        $this->assertSnapshotsCount(2);
        self::assertNotNull($staticSegment->getLastRun());
    }

    public function testRunWithSegmentFilter()
    {
        $staticSegment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);
        $this->manager->run($staticSegment);

        $staticSegmentWithFilter = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_SEGMENT_FILTER);
        $staticSegmentWithFilter->setRecordsLimit(7);

        $this->manager->run($staticSegmentWithFilter);
        $this->assertSnapshotsCount(57);
        self::assertNotNull($staticSegment->getLastRun());
    }
}
