<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityExtendBundle\Layout\Block\Type\AbstractAttributeType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

abstract class AbstractAttributeTypeTestCase extends BlockTypeTestCase
{
    /** @var AttributeManager|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeManager;

    /** @var AbstractAttributeType */
    private $testedType;

    protected function setUp()
    {
        parent::setUp();
        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testedType = $this->setType();
    }

    /**
     * @return AttributeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManager()
    {
        return $this->attributeManager;
    }

    /**
     * @return AbstractAttributeType $type
     */
    abstract protected function setType();

    public function testRequireOptions()
    {
        $resolver = new OptionsResolver();
        $this->testedType->configureOptions($resolver);
        $options = ['attribute' => new FieldConfigModel()];
        $actual = $resolver->resolve($options);
        $this->assertEquals($options, $actual);
    }

    public function testBuildView()
    {
        $field = (new FieldConfigModel())->setFieldName('type');
        $this->context->data()->set('entity', (new FieldConfigModel())->setType('Some type value'));
        $this->attributeManager->expects($this->once())
            ->method('getAttributeLabel')
            ->with($field)
            ->willReturn('Some label');
        $view = $this->getBlockView($this->testedType, ['attribute' => $field]);
        $this->assertEquals('Some type value', $view->vars['value']);
        $this->assertEquals('Some label', $view->vars['label']);
    }
}
