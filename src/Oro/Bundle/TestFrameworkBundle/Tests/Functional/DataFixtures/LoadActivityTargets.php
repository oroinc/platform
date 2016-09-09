<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class LoadActivityTargets extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $targetOne = new TestActivityTarget();
        $this->addReference('activity_target_one', $targetOne);
        $manager->persist($targetOne);

        $manager->flush();
    }
}
