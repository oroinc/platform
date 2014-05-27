<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;

class LoadWorkflowAwareEntityData extends AbstractFixture
{
    const COUNT = 50;
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= self::COUNT; $i++) {
            $entity = new WorkflowAwareEntity();
            $entity->setName('entity_' . $i);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
