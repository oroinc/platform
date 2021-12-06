<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AttributeFamilyRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadAttributeFamilyData::class]);
    }

    private function getRepository(): AttributeFamilyRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(AttributeFamily::class);
    }

    public function testGetFamiliesByAttributeIdEmpty()
    {
        $families = $this->getRepository()->getFamiliesByAttributeId(9999999);

        $this->assertCount(0, $families);
    }

    public function testGetFamiliesByAttributeId()
    {
        $families = $this->getRepository()->getFamiliesByAttributeId(
            LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1)
        );

        $this->assertCount(2, $families);
        $this->assertInstanceOf(AttributeFamily::class, reset($families));
        $this->assertEquals($this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1), $families[0]);
        $this->assertEquals($this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2), $families[1]);
    }

    public function testCountFamiliesByEntityClass()
    {
        $this->assertEquals(2, $this->getRepository()->countFamiliesByEntityClass(
            LoadAttributeData::ENTITY_CONFIG_MODEL
        ));
    }

    public function testCountFamiliesByEntityClassWithNotExistingEntityClass()
    {
        $this->assertEquals(0, $this->getRepository()->countFamiliesByEntityClass('NotExistingEntityClass'));
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

        $result = $this->getRepository()->getFamilyIdsForAttributes(
            [$attributeId1, $attributeId2, $attributeId3, $attributeId4]
        );

        $expected = [
            $attributeId1 => [$family1->getId(), $family2->getId()],
            $attributeId2 => [$family1->getId(), $family2->getId()],
            $attributeId3 => [$family1->getId()],
            $attributeId4 => [$family2->getId()],
        ];

        $this->assertFamilyIds($expected, $result);
    }

    public function testGetFamilyIdsForAttributesByOrganization()
    {
        /** @var AttributeFamily $family */
        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $organization = $family->getOwner();
        $attributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);

        $result = $this->getRepository()->getFamilyIdsForAttributesByOrganization([$attributeId], $organization);

        $expected = [
            $attributeId => [$family->getId()],
        ];

        $this->assertFamilyIds($expected, $result);
    }

    public function testGetFamilyIdsForAttributesByAnotherOrganization()
    {
        $attributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1);

        $organization = new Organization();
        $organization->setId(0);

        $this->assertEmpty(
            $this->getRepository()->getFamilyIdsForAttributesByOrganization([$attributeId], $organization)
        );
    }

    public function testGetFamilyByCode(): void
    {
        $family = $this->getRepository()->getFamilyByCode(
            LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1,
            $this->getContainer()->get('oro_security.acl_helper')
        );

        $this->assertInstanceOf(AttributeFamily::class, $family);

        $expectedFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $this->assertEquals($expectedFamily->getId(), $family->getId());
    }

    private function assertFamilyIds(array $expected, array $result): void
    {
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
