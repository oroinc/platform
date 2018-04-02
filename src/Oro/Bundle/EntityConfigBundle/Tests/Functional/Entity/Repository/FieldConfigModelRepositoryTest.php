<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
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

        // check only attributes added by this bundle because other bundles may add own attributes
        $expectedAttributes = [
            LoadAttributeData::SYSTEM_ATTRIBUTE_1,
            LoadAttributeData::SYSTEM_ATTRIBUTE_2,
            LoadAttributeData::REGULAR_ATTRIBUTE_1,
            LoadAttributeData::REGULAR_ATTRIBUTE_2,
            LoadAttributeData::NOT_USED_ATTRIBUTE
        ];
        foreach ($attributes as $attribute) {
            self::assertInstanceOf(FieldConfigModel::class, $attribute);
            $attributeName = $attribute->getFieldName();
            if (in_array($attributeName, $expectedAttributes, true)) {
                unset($expectedAttributes[array_search($attributeName, $expectedAttributes, true)]);
            }
        }
        self::assertEquals([], $expectedAttributes);
    }

    public function testGetActiveAttributesByClass()
    {
        /** @var AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getActiveAttributesByClass($family->getEntityClass());

        // check only attributes added by this bundle because other bundles may add own attributes
        $expectedAttributes = [
            LoadAttributeData::SYSTEM_ATTRIBUTE_1,
            LoadAttributeData::SYSTEM_ATTRIBUTE_2,
            LoadAttributeData::REGULAR_ATTRIBUTE_1,
            LoadAttributeData::REGULAR_ATTRIBUTE_2,
        ];
        foreach ($attributes as $attribute) {
            self::assertInstanceOf(FieldConfigModel::class, $attribute);
            $attributeName = $attribute->getFieldName();
            if (in_array($attributeName, $expectedAttributes, true)) {
                unset($expectedAttributes[array_search($attributeName, $expectedAttributes, true)]);
            }
            $extendOptions = $attribute->toArray('extend');
            self::assertEquals(ExtendScope::STATE_ACTIVE, $extendOptions['state'], $attributeName);
        }
        self::assertEquals([], $expectedAttributes);
    }

    public function testGetAttributesByClassAndIsSystemTrue()
    {
        /** @var AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getAttributesByClassAndIsSystem($family->getEntityClass(), true);

        // check only attributes added by this bundle because other bundles may add own attributes
        $expectedAttributes = [
            LoadAttributeData::SYSTEM_ATTRIBUTE_1,
            LoadAttributeData::SYSTEM_ATTRIBUTE_2,
        ];
        foreach ($attributes as $attribute) {
            self::assertInstanceOf(FieldConfigModel::class, $attribute);
            $attributeName = $attribute->getFieldName();
            if (in_array($attributeName, $expectedAttributes, true)) {
                unset($expectedAttributes[array_search($attributeName, $expectedAttributes, true)]);
            }
            $extendOptions = $attribute->toArray('extend');
            self::assertEquals(ExtendScope::OWNER_SYSTEM, $extendOptions['owner'], $attributeName);
        }
        self::assertEquals([], $expectedAttributes);
    }

    public function testGetAttributesByClassAndIsSystemFalse()
    {
        /** @var \Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributes = $this->repository->getAttributesByClassAndIsSystem($family->getEntityClass(), false);

        // check only attributes added by this bundle because other bundles may add own attributes
        $expectedAttributes = [
            LoadAttributeData::REGULAR_ATTRIBUTE_1,
            LoadAttributeData::REGULAR_ATTRIBUTE_2,
            LoadAttributeData::NOT_USED_ATTRIBUTE,
        ];
        foreach ($attributes as $attribute) {
            self::assertInstanceOf(FieldConfigModel::class, $attribute);
            $attributeName = $attribute->getFieldName();
            if (in_array($attributeName, $expectedAttributes, true)) {
                unset($expectedAttributes[array_search($attributeName, $expectedAttributes, true)]);
            }
            $extendOptions = $attribute->toArray('extend');
            self::assertEquals(ExtendScope::OWNER_CUSTOM, $extendOptions['owner'], $attributeName);
        }
        self::assertEquals([], $expectedAttributes);
    }
}
