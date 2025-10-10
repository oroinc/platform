<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntityRelatesToHidden;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedHiddenEntity;

/**
 * Migration that loading extend and relates hidden entities data for testing api and importexport
 */
class LoadTestHiddenEntitiesData extends AbstractFixture
{
    public const TEST_EXTEND_ENTITY_NAME_1 = 'test_extended_entity_name_1';
    public const TEST_EXTEND_ENTITY_NAME_2 = 'test_extended_entity_name_2';
    public const TEST_EXTEND_HIDDEN_ENTITY_NAME_1 = 'test_extended_hidden_entity_name_1';
    public const TEST_EXTEND_HIDDEN_ENTITY_NAME_2 = 'test_extended_hidden_entity_name_2';

    public function load(ObjectManager $manager)
    {
        $entities = [
            self::TEST_EXTEND_ENTITY_NAME_1 => [
                self::TEST_EXTEND_HIDDEN_ENTITY_NAME_1,
                self::TEST_EXTEND_HIDDEN_ENTITY_NAME_2
            ],
            self::TEST_EXTEND_ENTITY_NAME_2 => [
                self::TEST_EXTEND_HIDDEN_ENTITY_NAME_1,
                self::TEST_EXTEND_HIDDEN_ENTITY_NAME_2
            ]
        ];

        foreach ($entities as $parentName => $children) {
            $testEntityRelatesToHidden = new TestExtendedEntityRelatesToHidden();
            $testEntityRelatesToHidden->setTitle($parentName);
            foreach ($children as $child) {
                $testEntity = new TestExtendedHiddenEntity();
                $testEntity->setName($child);
                $testEntityRelatesToHidden->addTeeToHiddenOtm($testEntity);
                $testEntityRelatesToHidden->addTeeToHiddenMtm($testEntity);
                $manager->persist($testEntity);
                if (!$this->hasReference($child, TestExtendedHiddenEntity::class)) {
                    $this->addReference($child, $testEntity);
                }
            }
            $manager->persist($testEntityRelatesToHidden);
            $this->addReference($parentName, $testEntityRelatesToHidden);
        }

        $manager->flush();
    }
}
