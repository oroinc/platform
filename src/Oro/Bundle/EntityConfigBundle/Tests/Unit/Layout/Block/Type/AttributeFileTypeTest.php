<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeFileType;
use Oro\Bundle\EntityConfigBundle\Layout\Block\Type\AttributeTextType;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\Layout\Block\Type\AttributeType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Layouts;
use Symfony\Component\ExpressionLanguage\Expression;

class AttributeFileTypeTest extends BlockTypeTestCase
{
    private const NAME = 'attribute_file';

    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $attributeConfigurationProvider = $this->createMock(AttributeConfigurationProvider::class);
        $attributeTextType = new AttributeTextType($attributeConfigurationProvider);

        $attributeFileType = (new AttributeFileType())
            ->setParent('attribute_text')
            ->setName(self::NAME);

        $layoutFactoryBuilder
            ->addType($attributeFileType)
            ->addType($attributeTextType);

        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
    }

    public function testConfigureOptions(): void
    {
        $view = $this->getBlockView(
            self::NAME,
            [
                'entity' => new Expression('context["entity"]'),
                'fieldName' => 'attributeFieldName',
                'className' => 'attributeClassName',
            ]
        );

        $this->assertEquals(self::NAME, $view->vars['block_type']);
        $this->assertEquals('attributeFieldName', $view->vars['fieldName']);
        $this->assertEquals('attributeClassName', $view->vars['className']);
        $this->assertEquals(
            '=value !== null &&'
            . ' data["file_applications"].isValidForField(className, fieldName)',
            $view->vars['visible']
        );
    }

    public function testConfigureOptionsParentWithoutVisible(): void
    {
        $attributeType = new AttributeType('attribute_type');

        $attributeFileType = (new AttributeFileType())
            ->setParent('attribute_type')
            ->setName(self::NAME);

        $layoutFactoryBuilder = Layouts::createLayoutFactoryBuilder();
        $layoutFactoryBuilder
            ->addType($attributeFileType)
            ->addType($attributeType);
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
        $this->layoutFactory = $layoutFactoryBuilder->getLayoutFactory();

        $view = $this->getBlockView(self::NAME);
        $this->assertEquals(
            '=data["file_applications"].isValidForField(className, fieldName)',
            $view->vars['visible']
        );
    }
}
