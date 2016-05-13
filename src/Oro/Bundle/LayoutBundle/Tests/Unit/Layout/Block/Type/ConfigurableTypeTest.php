<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

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

    public function testBuildViewWithoutOptions()
    {
        $view = $this->getBlockBuilder($this->type, ['option_required' => true], 'test:block--1')
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView(
            [
                'vars' => [
                    'id' => 'test:block--1',
                    'block_type' => static::TYPE_NAME,
                    'unique_block_prefix' => '_test_block_1',
                    'block_prefixes' => [
                        'block',
                        static::TYPE_NAME,
                        '_test_block_1'
                    ],
                    'cache_key' => '_test:block--1_' . static::TYPE_NAME,
                    'translation_domain' => 'messages',
                    'option_default' => 'value',
                    'option_required' => true,
                ]
            ],
            $view,
            false
        );
    }

    public function testBuildView()
    {
        $options = [
            'vars' => ['test_var' => 'test_var_val'],
            'attr' => ['test_attr' => 'test_attr_val'],
            'label' => 'Test Label',
            'label_attr' => ['test_label_attr' => 'test_label_attr_val'],
            'translation_domain' => 'test_translation_domain',
            'option_required' => true,
        ];

        $view = $this->getBlockBuilder(static::TYPE_NAME, $options)
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $id = '_' . static::TYPE_NAME . '_id';
        $this->assertBlockView(
            [
                'vars' => [
                    'id' => static::TYPE_NAME . '_id',
                    'block_type' => static::TYPE_NAME,
                    'unique_block_prefix' => $id,
                    'block_prefixes' => [
                        'block',
                        static::TYPE_NAME,
                        $id
                    ],
                    'cache_key' => $id . '_' .static::TYPE_NAME,
                    'translation_domain' => 'test_translation_domain',
                    'option_default' => 'value',
                    'option_required' => true,
                    'test_var' => 'test_var_val',
                    'attr' => ['test_attr' => 'test_attr_val'],
                    'label' => 'Test Label',
                    'label_attr' => ['test_label_attr' => 'test_label_attr_val'],
                ]
            ],
            $view,
            false
        );
    }

    public function testSetOptionException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Option setting "test" not supported. Supported settings [default, required, normalizers, allowed_values]'
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
