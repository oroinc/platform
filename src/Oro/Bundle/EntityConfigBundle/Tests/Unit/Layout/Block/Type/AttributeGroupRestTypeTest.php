<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\BlockType;

use Symfony\Component\ExpressionLanguage\Expression;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeGroupRestType;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeGroupType;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

class AttributeGroupRestTypeTest extends BaseBlockTypeTestCase
{
    /** @var AttributeGroupRenderRegistry */
    protected $attributeGroupRenderRegistry;

    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->attributeGroupRenderRegistry = new AttributeGroupRenderRegistry;
        $restBlockType = new AttributeGroupRestType($this->attributeGroupRenderRegistry);

        $groupBlockTypeStub = new ConfigurableType();
        $groupBlockTypeStub->setName(AttributeGroupType::NAME);
        $groupBlockTypeStub->setOptionsConfig(
            [
                'entity' => ['required' => true],
                'group' => ['required' => true],
                'attribute_family' => ['required' => true],

            ]
        );
        $layoutFactoryBuilder
            ->addType($restBlockType)
            ->addType($groupBlockTypeStub);
    }

    public function testGetBlockView()
    {
        $entityValue = new Expression('context["entity"]');
        $attributeGroup1 = new AttributeGroup();
        $attributeGroup1->setCode('first_group');
        $attributeGroup2 = new AttributeGroup();
        $attributeGroup2->setCode('second_group');
        $attributeGroup3 = new AttributeGroup();
        $attributeGroup3->setCode('third_group');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup1);
        $attributeFamily->addAttributeGroup($attributeGroup2);
        $attributeFamily->addAttributeGroup($attributeGroup3);

        $this->attributeGroupRenderRegistry->setRendered($attributeFamily, $attributeGroup1);

        $view = $this->getBlockView(
            AttributeGroupRestType::NAME,
            [
                'attribute_family' => $attributeFamily,
                'entity' => $entityValue
            ]
        );
        $this->assertCount(2, $view->children);

        $secondAttributeGroup = $view->children['attribute_group_rest_id_attribute_group_second_group'];
        $this->assertEquals($entityValue, $secondAttributeGroup->vars['entity']);
        $this->assertEquals($attributeFamily, $secondAttributeGroup->vars['attribute_family']);
        $this->assertEquals('second_group', $secondAttributeGroup->vars['group']);

        $thirdAttributeGroup = $view->children['attribute_group_rest_id_attribute_group_third_group'];
        $this->assertEquals($entityValue, $thirdAttributeGroup->vars['entity']);
        $this->assertEquals($attributeFamily, $thirdAttributeGroup->vars['attribute_family']);
        $this->assertEquals('third_group', $thirdAttributeGroup->vars['group']);
    }

    public function testGetBlockViewNothingToRender()
    {
        $entityValue = new Expression('context["entity"]');
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('first_group');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup);

        $this->attributeGroupRenderRegistry->setRendered($attributeFamily, $attributeGroup);
        $view = $this->getBlockView(
            AttributeGroupRestType::NAME,
            [
                'attribute_family' => $attributeFamily,
                'entity' => $entityValue
            ]
        );

        $this->assertCount(0, $view->children);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(AttributeGroupRestType::NAME);

        $this->assertSame(AttributeGroupRestType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(AttributeGroupRestType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }

}
