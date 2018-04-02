<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;

class LoadSegmentSnapshotData extends AbstractFixture implements
    DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        /** @var WorkflowAwareEntity[] $entities */
        $entities = $manager->getRepository($segment->getEntity())->findAll();

        $segmentSnapshot = new SegmentSnapshot($segment);

        foreach ($entities as $entity) {
            $snapshot = clone $segmentSnapshot;
            $snapshot->setIntegerEntityId($entity->getId());

            $manager->persist($snapshot);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWorkflowAwareEntityData::class,
            LoadSegmentData::class,
        ];
    }
}
