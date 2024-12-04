<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeGroupData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AttributeGroupRelationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadAttributeFamilyData::class]);
    }

    private function getRepository(): AttributeGroupRelationRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(AttributeGroupRelation::class);
    }

    public function testGetAttributeFamiliesByAttributeIdsWithAcl()
    {
        $systemAttributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1);
        $regularAttributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);
        $aclHelper = $this
            ->getContainer()
            ->get('oro_security.acl_helper');
        $families = $this->getRepository()->getFamiliesLabelsByAttributeIdsWithAcl(
            [$systemAttributeId, $regularAttributeId],
            $aclHelper
        );

        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $expectedFamilies = [
            $systemAttributeId => [
                [
                    'id' => $family1->getId(),
                    'labels' => $family1->getLabels()
                ],
                [
                    'id' => $family2->getId(),
                    'labels' => $family2->getLabels()
                ]
            ],
            $regularAttributeId => [
                [
                    'id' => $family1->getId(),
                    'labels' => $family1->getLabels()
                ]
            ],
        ];

        $this->assertEquals(
            $this->prepareFamilyForComparison($expectedFamilies),
            $this->prepareFamilyForComparison($families)
        );
    }

    private function prepareFamilyForComparison(array $families): array
    {
        foreach ($families as $attributeId => $familyRows) {
            $families[$attributeId] = array_map(static function (array $row) {
                $row['labels'] = array_map(static function (LocalizedFallbackValue $value) {
                    return $value->getString();
                }, iterator_to_array($row['labels']));

                return $row;
            }, $familyRows);
        }

        return $families;
    }

    public function testGetAttributesMapByGroupIdsEmpty()
    {
        /** @var AttributeGroup $emptyAttributeGroup */
        $emptyAttributeGroup = $this->getReference(LoadAttributeGroupData::EMPTY_ATTRIBUTE_GROUP);
        $map = $this->getRepository()->getAttributesMapByGroupIds([$emptyAttributeGroup->getId()]);

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
        $map = $this->getRepository()->getAttributesMapByGroupIds([$defaultGroup->getId(), $regularGroup->getId()]);

        $this->assertCount(2, $map);
        $this->assertCount(2, $map[$defaultGroup->getId()]);
        $this->assertCount(2, $map[$regularGroup->getId()]);

        $expectedMap = [
            $defaultGroup->getId() => [
                LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1),
                LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_2),
            ],
            $regularGroup->getId() => [
                LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1),
                LoadAttributeData::getAttributeIdByName(LoadAttributeData::BOOL_ATTRIBUTE_1),
            ],
        ];

        $this->assertEquals($expectedMap, $map);
    }

    public function testRemoveByFieldId()
    {
        $regularAttributeId = LoadAttributeData::getAttributeIdByName(LoadAttributeData::REGULAR_ATTRIBUTE_1);
        $this->assertCount(1, $this->getRepository()->findBy(['entityConfigFieldId' => $regularAttributeId]));
        $this->assertEquals(1, $this->getRepository()->removeByFieldId($regularAttributeId));
        $this->assertEmpty($this->getRepository()->findBy(['entityConfigFieldId' => $regularAttributeId]));
    }

    public function testGetAttributeGroupRelationsByFamily()
    {
        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');

        $family = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $attributeGroupRelations = $this->getRepository()->getAttributeGroupRelationsByFamily($family);

        $this->assertCount(3, $attributeGroupRelations);

        usort($attributeGroupRelations, function (AttributeGroupRelation $a, AttributeGroupRelation $b) {
            return $a->getEntityConfigFieldId() - $b->getEntityConfigFieldId();
        });

        $systemAttribute1 = $configManager->getConfigFieldModel(
            LoadAttributeData::ENTITY_CONFIG_MODEL,
            LoadAttributeData::SYSTEM_ATTRIBUTE_1
        );
        $systemAttribute2 = $configManager->getConfigFieldModel(
            LoadAttributeData::ENTITY_CONFIG_MODEL,
            LoadAttributeData::SYSTEM_ATTRIBUTE_2
        );
        $regularAttribute1 = $configManager->getConfigFieldModel(
            LoadAttributeData::ENTITY_CONFIG_MODEL,
            LoadAttributeData::REGULAR_ATTRIBUTE_2
        );

        /** @var AttributeGroupRelation[] $expectedAttributes */
        $expectedAttributes = [];
        $expectedAttributes[$systemAttribute1->getId()] = $systemAttribute1;
        $expectedAttributes[$systemAttribute2->getId()] = $systemAttribute2;
        $expectedAttributes[$regularAttribute1->getId()] = $regularAttribute1;

        // Need to sort expected and returned values by entity config field id in the same way
        // to be able to assert them in order
        ksort($expectedAttributes);
        $expectedAttributes = array_values($expectedAttributes);

        $this->assertEquals($expectedAttributes[0]->getId(), $attributeGroupRelations[0]->getEntityConfigFieldId());
        $this->assertSame(
            $this->getReference(LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_2),
            $attributeGroupRelations[0]->getAttributeGroup()
        );

        $this->assertEquals($expectedAttributes[1]->getId(), $attributeGroupRelations[1]->getEntityConfigFieldId());
        $this->assertSame(
            $this->getReference(LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_2),
            $attributeGroupRelations[1]->getAttributeGroup()
        );

        $this->assertEquals($expectedAttributes[2]->getId(), $attributeGroupRelations[2]->getEntityConfigFieldId());
        $this->assertSame(
            $this->getReference(LoadAttributeGroupData::REGULAR_ATTRIBUTE_GROUP_2),
            $attributeGroupRelations[2]->getAttributeGroup()
        );
    }
}
