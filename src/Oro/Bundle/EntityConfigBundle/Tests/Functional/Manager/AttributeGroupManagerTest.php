<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Manager;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AttributeGroupManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testCreateGroupWithSystemAttributes()
    {
        $container = $this->getContainer();
        $attributeGroupManager = $container->get('oro_entity_config.manager.attribute_group_manager');
        $attributeGroup = $attributeGroupManager->createGroupWithSystemAttributes(Product::class);

        $this->assertInstanceOf(AttributeGroup::class, $attributeGroup);

        $attributeManager = $container->get('oro_entity_config.manager.attribute_manager');
        $systemAttributesCount = count($attributeManager->getSystemAttributesByClass(Product::class));

        $this->assertCount($systemAttributesCount, $attributeGroup->getAttributeRelations());
    }
}
