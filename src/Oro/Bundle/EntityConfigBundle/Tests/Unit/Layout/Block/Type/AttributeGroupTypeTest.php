<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\ExpressionLanguage\Expression;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeGroupType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

class AttributeGroupTypeTest extends BlockTypeTestCase
{
    /** @var  AttributeGroupRenderRegistry */
    protected $attributeGroupRenderRegistry;

    /** @var AttributeManager|\PHPUnit_Framework_MockObject_MockObject $attributeManager */
    protected $attributeManager;

    /** @var AttributeBlockTypeMapperInterface|\PHPUnit_Framework_MockObject_MockObject $blockTypeMapper */
    protected $blockTypeMapper;

    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $this->attributeGroupRenderRegistry = new AttributeGroupRenderRegistry();

        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->setMethods(['getAttributeLabel', 'getAttributesByGroup'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockTypeMapper = $this->createMock(AttributeBlockTypeMapperInterface::class);

        $attributeGroupType = new AttributeGroupType(
            $this->attributeGroupRenderRegistry,
            $this->attributeManager,
            $this->blockTypeMapper
        );

        $attributeType = new ConfigurableType();
        $attributeType->setName('attribute_type');
        $attributeType->setOptionsConfig(
            [
                'entity' => ['required' => true],
                'property_path' => ['required' => true],
                'label' => ['required' => true],
            ]
        );

        $layoutFactoryBuilder
            ->addType($attributeGroupType)
            ->addType($attributeType);

        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
    }

    public function testGetBlockView()
    {
        $firstAttribute = new FieldConfigModel('first_attribute', 'string');
        $secondAttribute = new FieldConfigModel('second_attribute', 'integer');

        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('group_code');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByGroup')
            ->with($attributeGroup)
            ->willReturn([$firstAttribute, $secondAttribute]);

        $this->attributeManager->expects($this->at(1))
            ->method('getAttributeLabel')
            ->with($firstAttribute)
            ->willReturn('first_attribute_label');

        $this->attributeManager->expects($this->at(2))
            ->method('getAttributeLabel')
            ->with($secondAttribute)
            ->willReturn('second_attribute_label');

        $this->blockTypeMapper->expects($this->any())
            ->method('getBlockType')
            ->willReturn('attribute_type');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup);

        $entityValue = new Expression('context["entity"]');

        $this->assertFalse($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));

        $view = $this->getBlockView(
            AttributeGroupType::NAME,
            [
                'group' => 'group_code',
                'entity' => $entityValue,
                'attribute_family' => $attributeFamily
            ]
        );
        $this->assertCount(2, $view->children);

        $firstAttributeView = $view->children['attribute_group_id_attribute_type_first_attribute'];
        $this->assertEquals('attribute_type', $firstAttributeView->vars['block_type']);
        $this->assertEquals($entityValue, $firstAttributeView->vars['entity']);
        $this->assertEquals('first_attribute', $firstAttributeView->vars['property_path']);
        $this->assertEquals('first_attribute_label', $firstAttributeView->vars['label']);

        $secondAttributeView = $view->children['attribute_group_id_attribute_type_second_attribute'];
        $this->assertEquals('attribute_type', $secondAttributeView->vars['block_type']);
        $this->assertEquals($entityValue, $secondAttributeView->vars['entity']);
        $this->assertEquals('second_attribute', $secondAttributeView->vars['property_path']);
        $this->assertEquals('second_attribute_label', $secondAttributeView->vars['label']);

        $this->assertTrue($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));
    }

    public function testGetBlockViewNotExcludeFromRest()
    {
        $attribute = new FieldConfigModel('attribute', 'string');

        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('group_code');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByGroup')
            ->with($attributeGroup)
            ->willReturn([$attribute]);

        $this->attributeManager->expects($this->once())
            ->method('getAttributeLabel')
            ->with($attribute)
            ->willReturn('attribute_label');

        $this->blockTypeMapper->expects($this->any())
            ->method('getBlockType')
            ->willReturn('attribute_type');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup);

        $this->assertFalse($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));

        $entityValue = new Expression('context["entity"]');
        $view = $this->getBlockView(
            AttributeGroupType::NAME,
            [
                'group' => 'group_code',
                'entity' => $entityValue,
                'attribute_family' => $attributeFamily,
                'exclude_from_rest' => false
            ]
        );
        $this->assertCount(1, $view->children);

        $firstAttributeView = $view->children['attribute_group_id_attribute_type_attribute'];
        $this->assertEquals('attribute_type', $firstAttributeView->vars['block_type']);
        $this->assertEquals($entityValue, $firstAttributeView->vars['entity']);
        $this->assertEquals('attribute', $firstAttributeView->vars['property_path']);
        $this->assertEquals('attribute_label', $firstAttributeView->vars['label']);
        $this->assertFalse($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));
    }

    public function testGetName()
    {
        $type = $this->getBlockType(AttributeGroupType::NAME);

        $this->assertSame(AttributeGroupType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(AttributeGroupType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
