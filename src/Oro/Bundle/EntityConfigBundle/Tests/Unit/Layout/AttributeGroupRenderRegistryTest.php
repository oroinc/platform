<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;

class AttributeGroupRenderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttributeGroupRenderRegistry */
    protected $attributeGroupRenderRegistry;

    protected function setUp()
    {
        $this->attributeGroupRenderRegistry = new AttributeGroupRenderRegistry();
    }

    public function testRendered()
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');

        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('group_code');
        $this->assertFalse($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));

        $this->attributeGroupRenderRegistry->setRendered($attributeFamily, $attributeGroup);

        $this->assertTrue($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));
    }

    public function testGetNotRenderedGroups()
    {
        $attributeFamily = new AttributeFamily();
        $familyCode = 'family_code';
        $attributeFamily->setCode($familyCode);

        $attributeGroup1 = new AttributeGroup();
        $groupCode = 'group_1_code';
        $attributeGroup1->setCode($groupCode);

        $attributeGroup2 = new AttributeGroup();
        $groupCode = 'group_2_code';
        $attributeGroup2->setCode($groupCode);

        $attributeGroup3 = new AttributeGroup();
        $groupCode = 'group_3_code';
        $attributeGroup3->setCode($groupCode);
        $attributeGroup3->setIsVisible(false);

        $attributeFamily
            ->addAttributeGroup($attributeGroup1)
            ->addAttributeGroup($attributeGroup2)
            ->addAttributeGroup($attributeGroup3);

        $this->assertCount(2, $this->attributeGroupRenderRegistry->getNotRenderedGroups($attributeFamily));

        $this->attributeGroupRenderRegistry->setRendered($attributeFamily, $attributeGroup1);

        $notRenderedGroups = $this->attributeGroupRenderRegistry->getNotRenderedGroups($attributeFamily);
        $this->assertCount(1, $notRenderedGroups);
        $this->assertSame($attributeGroup2, $notRenderedGroups->first());
    }
}
