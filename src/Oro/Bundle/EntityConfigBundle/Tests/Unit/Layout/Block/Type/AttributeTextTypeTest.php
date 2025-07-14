<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeTextType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\ExpressionLanguage\Expression;

class AttributeTextTypeTest extends BlockTypeTestCase
{
    private AttributeConfigurationProvider&MockObject $attributeConfigurationProvider;
    private AttributeTextType $attributeTextType;

    #[\Override]
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $this->attributeConfigurationProvider = $this->createMock(AttributeConfigurationProvider::class);

        $this->attributeTextType = new AttributeTextType($this->attributeConfigurationProvider);

        $layoutFactoryBuilder->addType($this->attributeTextType);

        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
    }

    public function testGetBlockView(): void
    {
        $attribute = new FieldConfigModel('attributeFieldName', 'string');
        $attribute->setEntity(new EntityConfigModel('attributeClassName'));

        $this->attributeConfigurationProvider->expects($this->once())
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
        $this->assertEquals('=value !== null', $view->vars['visible']);
    }

    public function testSetDefaultVisible(): void
    {
        $this->attributeTextType->setDefaultVisible('=value === null');

        $view = $this->getBlockView(
            AttributeTextType::NAME,
            [
                'entity' => new Expression('context["entity"]'),
                'fieldName' => 'attributeFieldName',
                'className' => 'attributeClassName',
            ]
        );

        $this->assertEquals('=value === null', $view->vars['visible']);
    }

    public function testGetParent(): void
    {
        $type = $this->getBlockType(AttributeTextType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
