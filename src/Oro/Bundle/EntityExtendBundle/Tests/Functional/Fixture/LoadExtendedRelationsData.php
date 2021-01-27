<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class LoadExtendedRelationsData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $owningEntity  = new \Extend\Entity\TestEntity1();
        $targetEntity1 = new \Extend\Entity\TestEntity2();
        $targetEntity2 = new \Extend\Entity\TestEntity2();

        $targetEntity1->setName('target1');
        $targetEntity2->setName('target2');

        $owningEntity->setName('owning1');

        // unidirectional many-to-one
        $owningEntity->setUniM2OTarget($targetEntity1);
        // bidirectional many-to-one
        $owningEntity->setBiM2OTarget($targetEntity1);

        // unidirectional many-to-many
        $owningEntity->addUniM2MTarget($targetEntity1);
        $owningEntity->addUniM2MTarget($targetEntity2);
        $owningEntity->setDefaultUniM2MTargets($targetEntity2);
        // unidirectional many-to-many without default
        $owningEntity->addUniM2MNDTarget($targetEntity1);
        $owningEntity->addUniM2MNDTarget($targetEntity2);
        // bidirectional many-to-many
        $owningEntity->addBiM2MTarget($targetEntity1);
        $owningEntity->addBiM2MTarget($targetEntity2);
        $owningEntity->setDefaultBiM2MTargets($targetEntity2);
        // bidirectional many-to-many without default
        $owningEntity->addBiM2MNDTarget($targetEntity1);
        $owningEntity->addBiM2MNDTarget($targetEntity2);

        // unidirectional one-to-many
        $owningEntity->addUniO2MTarget($targetEntity1);
        $owningEntity->addUniO2MTarget($targetEntity2);
        $owningEntity->setDefaultUniO2MTargets($targetEntity2);
        // unidirectional one-to-many without default
        $owningEntity->addUniO2MNDTarget($targetEntity1);
        $owningEntity->addUniO2MNDTarget($targetEntity2);
        // bidirectional one-to-many
        $owningEntity->addBiO2MTarget($targetEntity1);
        $owningEntity->addBiO2MTarget($targetEntity2);
        $owningEntity->setDefaultBiO2MTargets($targetEntity2);
        // bidirectional one-to-many without default
        $owningEntity->addBiO2MNDTarget($targetEntity1);
        $owningEntity->addBiO2MNDTarget($targetEntity2);

        $manager->persist($owningEntity);
        $manager->persist($targetEntity1);
        $manager->persist($targetEntity2);

        $manager->flush();
    }
}
