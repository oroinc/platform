<?php

namespace Oro\Component\Layout\Tests\Unit\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

class ContainerTypeTest extends BaseBlockTypeTestCase
{
    public function testBuildViewWithoutOptions()
    {
        $view = $this->getBlockBuilder(ContainerType::NAME, [])
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView(
            [
                'vars' => [
                    'id'                   => 'container_id',
                    'block_type'           => 'container',
                    'block_type_widget_id' => 'container_widget',
                    'unique_block_prefix'  => '_container_id',
                    'block_prefixes'       => [
                        'block',
                        'container',
                        '_container_id'
                    ],
                    'cache_key'            => '_container_id_container_ad7b81dea42cf2ef7525c274471e3ce6',
                    'translation_domain'   => 'messages',
                    '_blockThemes'         => [],
                    '_formThemes'          => [],
                ]
            ],
            $view,
            false
        );
    }

    public function testBuildView()
    {
        $options = [
            'attr'               => ['test_attr' => 'test_attr_val'],
            'label'              => 'Test Label',
            'label_attr'         => ['test_label_attr' => 'test_label_attr_val'],
            'translation_domain' => 'test_translation_domain'
        ];

        $view = $this->getBlockBuilder(ContainerType::NAME, $options)
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView(
            [
                'vars' => [
                    'id'                   => 'container_id',
                    'block_type'           => 'container',
                    'block_type_widget_id' => 'container_widget',
                    'unique_block_prefix'  => '_container_id',
                    'block_prefixes'       => [
                        'block',
                        'container',
                        '_container_id'
                    ],
                    'cache_key'            => '_container_id_container_ad7b81dea42cf2ef7525c274471e3ce6',
                    'translation_domain'   => 'test_translation_domain',
                    'attr'                 => ['test_attr' => 'test_attr_val'],
                    'label'                => 'Test Label',
                    'label_attr'           => ['test_label_attr' => 'test_label_attr_val'],
                    '_blockThemes'         => [],
                    '_formThemes'          => [],
                ]
            ],
            $view,
            false
        );
    }
}
