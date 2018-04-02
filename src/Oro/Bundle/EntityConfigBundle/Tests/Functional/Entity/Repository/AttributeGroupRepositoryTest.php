<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeGroupData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AttributeGroupRepositoryTest extends WebTestCase
{
    /**
     * @var AttributeGroupRepository
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
            ->getEntityRepositoryForClass(AttributeGroup::class);
    }

    public function testGetGroupsByIdsWithEmptyIdsArray()
    {
        $this->assertEquals([], $this->repository->getGroupsByIds([]));
    }

    public function testGetGroupsByIds()
    {
        $group1 = $this->getReference(LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_1);
        $group2 = $this->getReference(LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_2);
        $emptyGroup = $this->getReference(LoadAttributeGroupData::EMPTY_ATTRIBUTE_GROUP);

        $this->assertEquals(
            [
                $group1->getId() => $group1,
                $group2->getId() => $group2,
                $emptyGroup->getId() => $emptyGroup
            ],
            $this->repository->getGroupsByIds([
                $group1->getId(),
                $group2->getId(),
                $emptyGroup->getId()
            ])
        );
    }

    public function testGetGroupsWithAttributeRelations()
    {
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $this->assertEquals(
            [
                $this->getReference(LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_1),
                $this->getReference(LoadAttributeGroupData::REGULAR_ATTRIBUTE_GROUP_1)
            ],
            $this->repository->getGroupsWithAttributeRelations($attributeFamily)
        );
    }
}
