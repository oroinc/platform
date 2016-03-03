<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class LoadTestEntityData extends AbstractFixture
{
    const TEST_ENTITY_1 = 'test_entity_1';

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $testEntity = new TestActivity();
        $testEntity->setMessage('test message');
        $manager->persist($testEntity);
        $manager->flush();
        $this->addReference(self::TEST_ENTITY_1, $testEntity);
    }
}
