<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeTextType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Symfony\Component\ExpressionLanguage\Expression;

class AttributeTextTypeTest extends BlockTypeTestCase
{
    /** @var AttributeManager|\PHPUnit_Framework_MockObject_MockObject $attributeManager */
    protected $attributeManager;

    /**
     * @param LayoutFactoryBuilderInterface $layoutFactoryBuilder
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->setMethods(['getAttributeLabel'])
            ->disableOriginalConstructor()
            ->getMock();

        $attributeTextType = new AttributeTextType($this->attributeManager);

        $layoutFactoryBuilder
            ->addType($attributeTextType);

        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
    }

    public function testGetBlockView()
    {
        $attribute = new FieldConfigModel('attributeFieldName', 'string');
        $attribute->setEntity(new EntityConfigModel('attributeClassName'));


        $this->attributeManager->expects($this->once())
            ->method('getAttributeLabel')
            ->with($this->isInstanceOf(FieldConfigModel::class))
            ->willReturn('attribute_label');

        $entityValue = new Expression('context["entity"]');

        $view = $this->getBlockView(
            AttributeTextType::NAME,
            [
                'entity' => $entityValue,
                'fieldName' => 'attributeFieldName',
                'className' => 'attributeClassName',
            ]
        );

        $this->assertEquals($entityValue, $view->vars['entity']);
        $this->assertEquals('attributeFieldName', $view->vars['fieldName']);
        $this->assertEquals('attributeClassName', $view->vars['className']);
        $this->assertEquals('attribute_label', $view->vars['label']);
        $this->assertEquals('=data["property_accessor"].getValue(entity, fieldName)', $view->vars['value']);
        $this->assertEquals(
            '=data["attribute_config"].getConfig(className,fieldName).is("visible") && value !== null',
            $view->vars['visible']
        );
    }

    public function testGetName()
    {
        $type = $this->getBlockType(AttributeTextType::NAME);

        $this->assertSame(AttributeTextType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(AttributeTextType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
