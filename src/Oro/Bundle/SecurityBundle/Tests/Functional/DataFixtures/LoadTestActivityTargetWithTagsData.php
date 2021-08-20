<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class LoadTestActivityTargetWithTagsData extends AbstractFixtureWithTags
{
    public const ACTIVITY_TARGET_1 = 'test_activity_target_1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $product1 = new TestActivityTarget();
        $product1->setString($this->getTextWithTags());

        $manager->persist($product1);
        $manager->flush();

        $this->setReference(self::ACTIVITY_TARGET_1, $product1);
    }
}
