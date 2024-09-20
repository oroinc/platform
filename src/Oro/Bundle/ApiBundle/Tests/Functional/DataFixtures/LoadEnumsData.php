<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

class LoadEnumsData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i <= 4; $i++) {
            $enum = new EnumOption('api_enum1', sprintf('Item %d', $i), (string)$i, $i - 1, !(bool)$i);
            $this->addReference(sprintf('enum1_%d', $i), $enum);
            $manager->persist($enum);
        }
        for ($i = 1; $i <= 4; $i++) {
            $enum = new EnumOption('api_enum2', sprintf('Item %d', $i), (string)$i, $i - 1, !(bool)$i);
            $this->addReference(sprintf('enum2_%d', $i), $enum);
            $manager->persist($enum);
        }
        $manager->flush();
    }
}
