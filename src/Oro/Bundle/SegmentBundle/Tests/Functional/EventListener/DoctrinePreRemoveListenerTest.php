<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DoctrinePreRemoveListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadSegmentSnapshotData::class]);
    }

    private function getSegment(string $reference): Segment
    {
        return $this->getReference($reference);
    }

    private function getWorkflowAwareEntity(string $reference): WorkflowAwareEntity
    {
        return $this->getReference($reference);
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }

    public function testSegmentSnapshotActualizationOnEntityRemoveWhenExecutedWithinJob(): void
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);
        $entity = $this->getWorkflowAwareEntity('workflow_aware_entity_1');
        $entityId = $entity->getId();

        $segmentSnapshotRepository = $this->getEntityManager(SegmentSnapshot::class)
            ->getRepository(SegmentSnapshot::class);

        $qb = $segmentSnapshotRepository->getIdentifiersSelectQueryBuilder($segment);
        $snapshotIds = array_column(
            $qb->getQuery()->getArrayResult(),
            SegmentSnapshot::ENTITY_REF_INTEGER_FIELD
        );
        self::assertContains($entityId, $snapshotIds);

        $em = $this->getEntityManager(get_class($entity));
        $em->remove($entity);
        $em->flush();

        $qb = $segmentSnapshotRepository->getIdentifiersSelectQueryBuilder($segment);
        $snapshotIds = array_column(
            $qb->getQuery()->getArrayResult(),
            SegmentSnapshot::ENTITY_REF_INTEGER_FIELD
        );
        self::assertNotContains($entityId, $snapshotIds);
    }
}
