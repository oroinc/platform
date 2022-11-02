<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Manager;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AttributeGroupManagerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testCreateGroupsWithAttributes(): void
    {
        $groups = [
            [
                'groupLabel' => 'General',
                'groupCode' => 'general',
                'groupVisibility' => true,
            ],
            [
                'groupCode' => 'test1',
                'groupVisibility' => true,
            ],
            [
                'groupLabel' => 'Test2',
                'groupVisibility' => true,
            ],
            [
                'groupLabel' => 'Test3',
                'groupCode' => 'test3',
            ]
        ];

        $container = $this->getContainer();
        $attributeManager = $container->get('oro_entity_config.manager.attribute_manager');
        $groups[0]['attributes'] = array_map(
            static function (FieldConfigModel $item) {
                return $item->getFieldName();
            },
            $attributeManager->getSystemAttributesByClass(TestActivityTarget::class)
        );

        $attributeGroupManager = $container->get('oro_entity_config.manager.attribute_group_manager');
        $attributeGroups = $attributeGroupManager->createGroupsWithAttributes(TestActivityTarget::class, $groups);

        $this->assertIsArray($attributeGroups);
        $this->assertCount(1, $attributeGroups);

        $this->assertCount(count($groups[0]['attributes']), $attributeGroups[0]->getAttributeRelations());
    }
}
