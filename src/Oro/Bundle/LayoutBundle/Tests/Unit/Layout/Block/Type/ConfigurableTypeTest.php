<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;

class ConfigurableTypeTest extends BaseBlockTypeTestCase
{
    const TYPE_NAME = 'custom_type';

    /**
     * @var ConfigurableType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->type = new ConfigurableType();
        $this->type->setName(static::TYPE_NAME)
            ->setOptions([
                'option' => null,
                'option_default' => ['default' => 'value'],
                'option_required' => ['required' => true],
            ]);
        $layoutFactoryBuilder->addType($this->type);
    }

    public function testSetDefaultOptionsWithEmptyOptions()
    {
        $this->assertEquals([
            'option_default' => 'value',
            'option_required' => 'required_value',
        ], $this->resolveOptions(static::TYPE_NAME, ['option_required' => 'required_value']));
    }

    public function testSetDefaultOptionsWithValidOptions()
    {
        $this->assertEquals([
            'option' => 'value',
            'option_default' => 'default_value',
            'option_required' => 'required_value',
        ], $this->resolveOptions(static::TYPE_NAME, [
            'option' => 'value',
            'option_default' => 'default_value',
            'option_required' => 'required_value',
        ]));
    }

    public function testSetOptionException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Option setting "test" not supported. Supported settings [default, required]'
        );
        $this->type->setOptions(['test' => ['test' => 'value']]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Block type "name" does not configured
     */
    public function testGetNameException()
    {
        (new ConfigurableType())->getName();
    }

    public function testGetName()
    {
        $this->assertEquals(static::TYPE_NAME, $this->type->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Block type "name" should be string
     */
    public function testSetNameExceptionType()
    {
        $this->type->setName([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Block type "parent" should be string
     */
    public function testSetParentExceptionType()
    {
        $this->type->setParent([]);
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseType::NAME, $this->type->getParent());
        $this->type->setParent('test');
        $this->assertEquals('test', $this->type->getParent());
    }
}
