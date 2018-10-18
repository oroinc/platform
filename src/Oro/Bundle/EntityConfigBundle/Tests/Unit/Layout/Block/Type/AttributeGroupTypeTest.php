<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeGroupType;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Symfony\Component\ExpressionLanguage\Expression;

class AttributeGroupTypeTest extends BlockTypeTestCase
{
    /** @var AttributeRenderRegistry */
    protected $attributeRenderRegistry;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject $attributeManager */
    protected $attributeManager;

    /** @var AttributeBlockTypeMapperInterface|\PHPUnit\Framework\MockObject\MockObject $blockTypeMapper */
    protected $blockTypeMapper;

    /**
     * @param LayoutFactoryBuilderInterface $layoutFactoryBuilder
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $this->attributeRenderRegistry = new AttributeRenderRegistry();

        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->setMethods(['getAttributesByGroup'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockTypeMapper = $this->createMock(AttributeBlockTypeMapperInterface::class);

        $attributeGroupType = new AttributeGroupType(
            $this->attributeRenderRegistry,
            $this->attributeManager,
            $this->blockTypeMapper
        );

        $attributeType = new ConfigurableType();
        $attributeType->setName('attribute_type');
        $attributeType->setOptionsConfig(
            [
                'entity' => ['required' => true],
                'fieldName' => ['required' => true],
                'className' => ['required' => true],
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
        $firstAttribute->setEntity(new EntityConfigModel('firstAttributeClassName'));
        $secondAttribute = new FieldConfigModel('second_attribute', 'integer');
        $secondAttribute->setEntity(new EntityConfigModel('secondAttributeClassName'));
        $thirdAttribute = new FieldConfigModel('third_attribute', 'integer');
        $thirdAttribute->setEntity(new EntityConfigModel('thirdAttributeClassName'));

        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('group_code');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByGroup')
            ->with($attributeGroup)
            ->willReturn([$firstAttribute, $secondAttribute, $thirdAttribute]);

        $this->blockTypeMapper->expects($this->any())
            ->method('getBlockType')
            ->willReturn('attribute_type');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup);

        $this->attributeRenderRegistry->setAttributeRendered($attributeFamily, 'third_attribute');

        $entityValue = new Expression('context["entity"]');

        $this->assertFalse($this->attributeRenderRegistry->isGroupRendered($attributeFamily, $attributeGroup));

        $view = $this->getBlockView(
            AttributeGroupType::NAME,
            [
                'group' => 'group_code',
                'entity' => $entityValue,
                'attribute_family' => $attributeFamily,
                'attribute_options' => [
                    'vars' => ['foo' => 'bar']
                ]
            ]
        );
        $this->assertCount(2, $view->children);

        $firstAttributeView = $view->children['attribute_group_id_attribute_type_first_attribute'];
        $this->assertEquals('attribute_type', $firstAttributeView->vars['block_type']);
        $this->assertEquals($entityValue, $firstAttributeView->vars['entity']);
        $this->assertEquals('first_attribute', $firstAttributeView->vars['fieldName']);
        $this->assertEquals('firstAttributeClassName', $firstAttributeView->vars['className']);
        $this->assertEquals('bar', $firstAttributeView->vars['foo']);

        $secondAttributeView = $view->children['attribute_group_id_attribute_type_second_attribute'];
        $this->assertEquals('attribute_type', $secondAttributeView->vars['block_type']);
        $this->assertEquals($entityValue, $secondAttributeView->vars['entity']);
        $this->assertEquals('second_attribute', $secondAttributeView->vars['fieldName']);
        $this->assertEquals('secondAttributeClassName', $secondAttributeView->vars['className']);
        $this->assertEquals('bar', $secondAttributeView->vars['foo']);

        $this->assertTrue($this->attributeRenderRegistry->isGroupRendered($attributeFamily, $attributeGroup));
    }

    public function testGetBlockViewWithNonExistentAttributeGroup()
    {
        $this->attributeManager->expects($this->never())
            ->method('getAttributesByGroup');

        $this->blockTypeMapper->expects($this->never())
            ->method('getBlockType');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');

        $entityValue = new Expression('context["entity"]');

        $view = $this->getBlockView(
            AttributeGroupType::NAME,
            [
                'group' => 'group_code',
                'entity' => $entityValue,
                'attribute_family' => $attributeFamily,
                'attribute_options' => [
                    'vars' => ['foo' => 'bar']
                ]
            ]
        );

        $this->assertCount(0, $view->children);
    }

    public function testGetBlockViewNotExcludeFromRest()
    {
        $attribute = new FieldConfigModel('attribute', 'string');
        $attribute->setEntity(new EntityConfigModel('attributeClassName'));
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('group_code');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByGroup')
            ->with($attributeGroup)
            ->willReturn([$attribute]);

        $this->blockTypeMapper->expects($this->any())
            ->method('getBlockType')
            ->willReturn('attribute_type');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup);

        $this->assertFalse($this->attributeRenderRegistry->isGroupRendered($attributeFamily, $attributeGroup));

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
        $this->assertEquals('attribute', $firstAttributeView->vars['fieldName']);
        $this->assertEquals('attributeClassName', $firstAttributeView->vars['className']);
        $this->assertFalse($this->attributeRenderRegistry->isGroupRendered($attributeFamily, $attributeGroup));
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(AttributeGroupType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
