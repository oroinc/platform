<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\EventListener;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DoctrinePreRemoveListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadSegmentSnapshotData::class,
        ]);
    }

    public function testSegmentSnapshotActualizationOnEntityRemoveWhenExecutedWithinJob(): void
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $registry = $this->getContainer()->get('doctrine');

        $entity = $this->getReference('workflow_aware_entity_1');
        $entityId = $entity->getId();

        $segmentSnapshotRepository = $registry->getManagerForClass(SegmentSnapshot::class)
            ->getRepository(SegmentSnapshot::class);

        $qb = $segmentSnapshotRepository->getIdentifiersSelectQueryBuilder($segment);
        $snapshotIds = array_column(
            $qb->getQuery()->getArrayResult(),
            SegmentSnapshot::ENTITY_REF_INTEGER_FIELD
        );
        $this->assertContains($entityId, $snapshotIds);

        $em = $registry->getManagerForClass(get_class($entity));
        $em->remove($entity);
        $em->flush();

        $qb = $segmentSnapshotRepository->getIdentifiersSelectQueryBuilder($segment);
        $snapshotIds = array_column(
            $qb->getQuery()->getArrayResult(),
            SegmentSnapshot::ENTITY_REF_INTEGER_FIELD
        );
        $this->assertNotContains($entityId, $snapshotIds);
    }
}
