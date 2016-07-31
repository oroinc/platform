<?php

namespace Oro\Component\Layout\Tests\Unit\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

class BaseTypeTest extends BaseBlockTypeTestCase
{
    public function testSetDefaultOptionsWithEmptyOptions()
    {
        $this->assertEquals(
            [],
            $this->resolveOptions(BaseType::NAME, [])
        );
    }

    public function testSetDefaultOptionsWithValidOptions()
    {
        $this->assertEquals(
            [
                'vars'               => ['test_var' => 'test_var_val'],
                'attr'               => ['test_attr' => 'test_attr_val'],
                'label'              => 'Test Label',
                'label_attr'         => ['test_label_attr' => 'test_label_attr_val'],
                'translation_domain' => 'test_translation_domain'
            ],
            $this->resolveOptions(
                BaseType::NAME,
                [
                    'vars'               => ['test_var' => 'test_var_val'],
                    'attr'               => ['test_attr' => 'test_attr_val'],
                    'label'              => 'Test Label',
                    'label_attr'         => ['test_label_attr' => 'test_label_attr_val'],
                    'translation_domain' => 'test_translation_domain'
                ]
            )
        );
    }

    public function testBuildViewWithoutOptions()
    {
        $view = $this->getBlockBuilder(BaseType::NAME, [], 'test:block--1')
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView(
            [
                'vars' => [
                    'id'                  => 'test:block--1',
                    'block_type'          => 'block',
                    'unique_block_prefix' => '_test_block_1',
                    'block_prefixes'      => [
                        'block',
                        '_test_block_1'
                    ],
                    'cache_key'           => '_test:block--1_block',
                    'translation_domain'  => 'messages'
                ]
            ],
            $view,
            false
        );
    }

    public function testBuildView()
    {
        $options = [
            'vars'               => ['test_var' => 'test_var_val'],
            'attr'               => ['test_attr' => 'test_attr_val'],
            'label'              => 'Test Label',
            'label_attr'         => ['test_label_attr' => 'test_label_attr_val'],
            'translation_domain' => 'test_translation_domain',
            'additional_block_prefix'=> 'additional_prefix'
        ];

        $view = $this->getBlockBuilder(BaseType::NAME, $options)
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView(
            [
                'vars' => [
                    'id'                  => 'block_id',
                    'block_type'          => 'block',
                    'unique_block_prefix' => '_block_id',
                    'block_prefixes'      => [
                        'block',
                        'additional_prefix',
                        '_block_id'
                    ],
                    'cache_key'           => '_block_id_block',
                    'translation_domain'  => 'test_translation_domain',
                    'attr'                => ['test_attr' => 'test_attr_val'],
                    'label'               => 'Test Label',
                    'label_attr'          => ['test_label_attr' => 'test_label_attr_val'],
                    'test_var'            => 'test_var_val'
                ]
            ],
            $view,
            false
        );
    }
}
