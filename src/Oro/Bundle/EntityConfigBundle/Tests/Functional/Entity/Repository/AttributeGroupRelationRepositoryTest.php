<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeGroupData;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AttributeGroupRelationRepositoryTest extends WebTestCase
{
    /**
     * @var AttributeGroupRelationRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadAttributeFamilyData::class,
        ]);

        $this->repository = $this
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(AttributeGroupRelation::class);
    }

    public function testGetAttributeFamiliesByAttributeIds()
    {
        $systemAttributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1);
        $regularAttributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);
        $families = $this->repository->getFamiliesLabelsByAttributeIds([$systemAttributeId, $regularAttributeId]);

        $expectedFamilies = [
            $systemAttributeId => [
                $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1),
                $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2)
            ],
            $regularAttributeId => [
                $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1)
            ],
        ];

        $this->assertEquals($expectedFamilies, $families);
    }

    public function testGetAttributesMapByGroupIdsEmpty()
    {
        /** @var AttributeGroup $emptyAttributeGroup */
        $emptyAttributeGroup = $this->getReference(LoadAttributeGroupData::EMPTY_ATTRIBUTE_GROUP);
        $map = $this->repository->getAttributesMapByGroupIds([$emptyAttributeGroup->getId()]);

        $this->assertCount(0, $map);
    }

    public function testGetAttributesMapByGroupIds()
    {
        /**
         * @var AttributeGroup $defaultGroup
         * @var AttributeGroup $regularGroup
         */
        $defaultGroup = $this->getReference(LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_1);
        $regularGroup = $this->getReference(LoadAttributeGroupData::REGULAR_ATTRIBUTE_GROUP_1);
        $map = $this->repository->getAttributesMapByGroupIds([$defaultGroup->getId(), $regularGroup->getId()]);

        $this->assertCount(2, $map);
        $this->assertCount(2, $map[$defaultGroup->getId()]);
        $this->assertCount(1, $map[$regularGroup->getId()]);

        $expectedMap = [
            $defaultGroup->getId() => [
                LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1),
                LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_2),
            ],
            $regularGroup->getId() => [
                LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1),
            ],
        ];

        $this->assertEquals($expectedMap, $map);
    }

    public function testRemoveByFieldId()
    {
        $regularAttributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);
        $this->assertCount(1, $this->repository->findBy(['entityConfigFieldId' => $regularAttributeId]));
        $this->assertEquals(1, $this->repository->removeByFieldId($regularAttributeId));
        $this->assertEmpty($this->repository->findBy(['entityConfigFieldId' => $regularAttributeId]));
    }
}
