<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Extend\Entity\EV_Api_Enum1 as TestEnum1;
use Extend\Entity\EV_Api_Enum2 as TestEnum2;

class LoadEnumsData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 4; $i++) {
            $enum = new TestEnum1(sprintf('item%d', $i), sprintf('Item %d', $i), $i - 1, !(bool)$i);
            $this->addReference(sprintf('enum1_%d', $i), $enum);
            $manager->persist($enum);
        }
        for ($i = 1; $i <= 4; $i++) {
            $enum = new TestEnum2(sprintf('item%d', $i), sprintf('Item %d', $i), $i - 1, !(bool)$i);
            $this->addReference(sprintf('enum2_%d', $i), $enum);
            $manager->persist($enum);
        }
        $manager->flush();
    }
}
