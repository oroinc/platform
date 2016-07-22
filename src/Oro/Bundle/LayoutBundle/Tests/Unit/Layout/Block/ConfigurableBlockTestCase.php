<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block;

use Oro\Bundle\LayoutBundle\Layout\Block\OptionsConfigTrait;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

class ConfigurableBlockTestCase extends BaseBlockTypeTestCase
{
    const TYPE_NAME = 'custom_type';

    /**
     * @var BlockTypeInterface
     */
    protected $type;

    /**
     * @return array
     */
    protected function getOptionsConfig()
    {
        return [
            'option' => null,
            'option_default' => ['default' => 'value'],
            'option_default_false' => ['default' => false],
            'option_default_null' => ['default' => null],
            'option_required' => ['required' => true],
        ];
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
                    'option_default_false' => false,
                    'option_default_null' => null,
                    'option_required' => true,
                ]
            ],
            $view,
            false
        );
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testBuildView(array $options, array $expected)
    {
        $view = $this->getBlockBuilder(static::TYPE_NAME, $options)
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView($expected, $view, false);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        $id = '_' . static::TYPE_NAME . '_id';
        $options = [
            'vars' => ['test_var' => 'test_var_val'],
            'attr' => ['test_attr' => 'test_attr_val'],
            'label' => 'Test Label',
            'label_attr' => ['test_label_attr' => 'test_label_attr_val'],
            'translation_domain' => 'test_translation_domain',
        ];
        $expected = [
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
            'option_default_false' => false,
            'option_default_null' => null,
            'test_var' => 'test_var_val',
            'attr' => ['test_attr' => 'test_attr_val'],
            'label' => 'Test Label',
            'label_attr' => ['test_label_attr' => 'test_label_attr_val'],
        ];
        return [
            [
                'options' => array_merge($options, [
                    'option' => null,
                    'option_required' => true,
                ]),
                'expected' => ['vars' => array_merge($expected, [
                    'option_required' => true,
                ])],
            ],
            [
                'options' => array_merge($options, [
                    'option' => false,
                    'option_required' => true,
                ]),
                'expected' => ['vars' => array_merge($expected, [
                    'option' => false,
                    'option_required' => true,
                ])],
            ],
        ];
    }

    public function testSetDefaultOptionsWithEmptyOptions()
    {
        $this->assertEquals([
            'option_default' => 'value',
            'option_required' => 'required_value',
            'option_default_false' => false,
            'option_default_null' => null,
        ], $this->resolveOptions(static::TYPE_NAME, ['option_required' => 'required_value']));
    }

    public function testSetDefaultOptionsWithValidOptions()
    {
        $this->assertEquals([
            'option' => 'value',
            'option_default' => 'default_value',
            'option_required' => 'required_value',
            'option_default_false' => false,
            'option_default_null' => null,
        ], $this->resolveOptions(static::TYPE_NAME, [
            'option' => 'value',
            'option_default' => 'default_value',
            'option_required' => 'required_value',
        ]));
    }

    /**
     * @param OptionsConfigTrait $object
     */
    protected function assertSetOptionException($object)
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Option setting "test" not supported. Supported settings is [default, required]'
        );
        $object->setOptionsConfig(['test' => ['test' => 'value']]);
    }
}
