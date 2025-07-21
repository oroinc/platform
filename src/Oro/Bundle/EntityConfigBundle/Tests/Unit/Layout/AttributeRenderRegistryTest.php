<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use PHPUnit\Framework\TestCase;

class AttributeRenderRegistryTest extends TestCase
{
    private AttributeRenderRegistry $attributeRenderRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->attributeRenderRegistry = new AttributeRenderRegistry();
    }

    public function testGroupRendered(): void
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');

        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('group_code');
        $this->assertFalse($this->attributeRenderRegistry->isGroupRendered($attributeFamily, $attributeGroup));

        $this->attributeRenderRegistry->setGroupRendered($attributeFamily, $attributeGroup);

        $this->assertTrue($this->attributeRenderRegistry->isGroupRendered($attributeFamily, $attributeGroup));
    }

    public function testGetNotRenderedGroups(): void
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

        $this->assertCount(2, $this->attributeRenderRegistry->getNotRenderedGroups($attributeFamily));

        $this->attributeRenderRegistry->setGroupRendered($attributeFamily, $attributeGroup1);

        $notRenderedGroups = $this->attributeRenderRegistry->getNotRenderedGroups($attributeFamily);
        $this->assertCount(1, $notRenderedGroups);
        $this->assertSame($attributeGroup2, $notRenderedGroups->first());
    }

    public function testAttributeRendered(): void
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');

        $this->assertFalse($this->attributeRenderRegistry->isAttributeRendered($attributeFamily, 'attribute'));

        $this->attributeRenderRegistry->setAttributeRendered($attributeFamily, 'attribute');

        $this->assertTrue($this->attributeRenderRegistry->isAttributeRendered($attributeFamily, 'attribute'));
    }
}
