<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AttributeFamilyRepositoryTest extends WebTestCase
{
    /**
     * @var AttributeFamilyRepository
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
            ->getEntityRepositoryForClass(AttributeFamily::class);
    }

    public function testGetFamiliesByAttributeIdEmpty()
    {
        $families = $this->repository->getFamiliesByAttributeId(
            LoadAttributeData::getAttributeIdByName(LoadAttributeData::NOT_USED_ATTRIBUTE)
        );

        $this->assertCount(0, $families);
    }

    public function testGetFamiliesByAttributeId()
    {
        $families = $this->repository->getFamiliesByAttributeId(
            LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1)
        );

        $this->assertCount(2, $families);
        $this->assertInstanceOf(AttributeFamily::class, reset($families));
        $this->assertEquals($this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1), $families[0]);
        $this->assertEquals($this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2), $families[1]);
    }

    public function testCountFamiliesByEntityClass()
    {
        $this->assertEquals(2, $this->repository->countFamiliesByEntityClass(
            LoadAttributeData::ENTITY_CONFIG_MODEL
        ));
    }

    public function testCountFamiliesByEntityClassWithNotExistingEntityClass()
    {
        $this->assertEquals(0, $this->repository->countFamiliesByEntityClass('NotExistingEntityClass'));
    }

    public function testGetFamilyIdsForAttributes()
    {
        $attributeId1 = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1);
        $attributeId2 = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_2);
        $attributeId3 = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);
        $attributeId4 = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_2);

        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $result = $this->repository->getFamilyIdsForAttributes(
            [$attributeId1, $attributeId2, $attributeId3, $attributeId4]
        );

        $expected = [
            $attributeId1 => [$family1->getId(), $family2->getId()],
            $attributeId2 => [$family1->getId(), $family2->getId()],
            $attributeId3 => [$family1->getId()],
            $attributeId4 => [$family2->getId()],
        ];

        $this->assertCount(count($expected), $result);

        foreach ($expected as $attributeId => $familyIds) {
            $this->assertArrayHasKey($attributeId, $result);
            $this->assertCount(count($familyIds), $result[$attributeId]);

            foreach ($familyIds as $familyId) {
                $this->assertContains($familyId, $result[$attributeId]);
            }
        }
    }
}
