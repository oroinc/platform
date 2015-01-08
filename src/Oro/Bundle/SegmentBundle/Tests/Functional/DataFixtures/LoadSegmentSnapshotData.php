<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;

class LoadSegmentSnapshotData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $segments = $manager->getRepository('OroSegmentBundle:Segment')->findAll();
        $entities = $manager->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity')->findAll();

        $entityCount = count($entities);
        /** @var Segment $segment */
        foreach ($segments as $segment) {
            $randomStart = rand(0, $entityCount);
            $randomEnd = rand($randomStart, $entityCount - $randomStart);
            /** @var WorkflowAwareEntity $entity */
            foreach ($entities as $key => $entity) {
                if ($key < $randomStart) {
                    continue;
                }

                if ($key > $randomEnd) {
                    break;
                }

                $segmentSnapshot = new SegmentSnapshot($segment);
                $segmentSnapshot->setEntityId($entity->getId());
                $segmentSnapshot->setCreatedAt(new \DateTime());
                $manager->persist($segmentSnapshot);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            'Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntityData',
            'Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData'
        );
    }
}
