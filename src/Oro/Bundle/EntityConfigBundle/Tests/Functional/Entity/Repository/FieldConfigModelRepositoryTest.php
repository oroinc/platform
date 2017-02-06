<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FieldConfigModelRepositoryTest extends WebTestCase
{
    /**
     * @var FieldConfigModelRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadAttributeFamilyData::class,
        ]);

        $this->repository = $this
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(FieldConfigModel::class);
    }

    public function testGetAttributesByIdsEmpty()
    {
        $attributes = $this->repository->getAttributesByIds([]);

        $this->assertCount(0, $attributes);
    }

    public function testGetAttributesByIds()
    {
        $attribute1Id = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1);
        $attribute2Id = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_2);
        $attribute3Id = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);
        $attributes = $this->repository->getAttributesByIds([$attribute1Id, $attribute2Id, $attribute3Id]);

        $this->assertCount(3, $attributes);
        $this->assertInstanceOf(FieldConfigModel::class, reset($attributes));
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_1, $attributes[$attribute1Id]->getFieldName());
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_2, $attributes[$attribute2Id]->getFieldName());
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_1, $attributes[$attribute3Id]->getFieldName());
    }

    public function testGetAttributesByIdsWithIndexEmpty()
    {
        $attributes = $this->repository->getAttributesByIdsWithIndex([]);

        $this->assertCount(0, $attributes);
    }

    public function testGetAttributesByIdsWithIndex()
    {
        $attributeId1 = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1);
        $attributeId2 = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_2);
        $attributeId3 = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);
        $attributes = $this->repository->getAttributesByIdsWithIndex([
            $attributeId1,
            $attributeId2,
            $attributeId3,
        ]);

        $this->assertCount(3, $attributes);
        $this->assertInstanceOf(FieldConfigModel::class, reset($attributes));
        $this->assertEquals([$attributeId1, $attributeId2, $attributeId3], array_keys($attributes));
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_1, $attributes[$attributeId1]->getFieldName());
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_2, $attributes[$attributeId2]->getFieldName());
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_1, $attributes[$attributeId3]->getFieldName());
    }

    public function testGetAttributesByClassEmpty()
    {
        /** @var \Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getAttributesByClass($family->getEntityClass().'FalseClass');

        $this->assertCount(0, $attributes);
    }

    public function testGetAttributesByClass()
    {
        /** @var AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getAttributesByClass($family->getEntityClass());

        $this->assertCount(5, $attributes);
        $this->assertInstanceOf(FieldConfigModel::class, reset($attributes));
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_1, $attributes[0]->getFieldName());
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_2, $attributes[1]->getFieldName());
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_1, $attributes[2]->getFieldName());
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_2, $attributes[3]->getFieldName());
        $this->assertEquals(LoadAttributeData::NOT_USED_ATTRIBUTE, $attributes[4]->getFieldName());
    }

    public function testGetActiveAttributesByClass()
    {
        /** @var AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getActiveAttributesByClass($family->getEntityClass());

        $this->assertCount(4, $attributes);
        $this->assertInstanceOf(FieldConfigModel::class, reset($attributes));
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_1, $attributes[0]->getFieldName());
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_2, $attributes[1]->getFieldName());
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_1, $attributes[2]->getFieldName());
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_2, $attributes[3]->getFieldName());
    }

    public function testGetAttributesByClassAndIsSystemTrue()
    {
        /** @var AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getAttributesByClassAndIsSystem($family->getEntityClass(), true);

        $this->assertCount(2, $attributes);
        $this->assertInstanceOf(FieldConfigModel::class, reset($attributes));
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_1, $attributes[0]->getFieldName());
        $this->assertEquals(LoadAttributeData::SYSTEM_ATTRIBUTE_2, $attributes[1]->getFieldName());
    }

    public function testGetAttributesByClassAndIsSystemFalse()
    {
        /** @var \Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getAttributesByClassAndIsSystem($family->getEntityClass(), false);

        $this->assertCount(3, $attributes);
        $this->assertInstanceOf(FieldConfigModel::class, reset($attributes));
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_1, $attributes[0]->getFieldName());
        $this->assertEquals(LoadAttributeData::REGULAR_ATTRIBUTE_2, $attributes[1]->getFieldName());
        $this->assertEquals(LoadAttributeData::NOT_USED_ATTRIBUTE, $attributes[2]->getFieldName());
    }
}
