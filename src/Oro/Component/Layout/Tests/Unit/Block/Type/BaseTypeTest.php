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
                'attr'               => ['test_attr' => 'test_attr_val'],
                'label'              => 'Test Label',
                'label_attr'         => ['test_label_attr' => 'test_label_attr_val'],
                'translation_domain' => 'test_translation_domain'
            ],
            $this->resolveOptions(
                BaseType::NAME,
                [
                    'attr'               => ['test_attr' => 'test_attr_val'],
                    'label'              => 'Test Label',
                    'label_attr'         => ['test_label_attr' => 'test_label_attr_val'],
                    'translation_domain' => 'test_translation_domain'
                ]
            )
        );
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testSetDefaultOptionsWithInvalidAttr()
    {
        $this->resolveOptions(BaseType::NAME, ['attr' => 'test_attr']);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testSetDefaultOptionsWithInvalidLabelAttr()
    {
        $this->resolveOptions(BaseType::NAME, ['label_attr' => 'test_label_attr']);
    }

    public function testBuildViewWithoutOptions()
    {
        $view = $this->getBlockBuilder(BaseType::NAME, [])
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView(
            [
                'vars' => [
                    'id'                  => 'block_id',
                    'unique_block_prefix' => '_block_id',
                    'block_prefixes'      => [
                        'block',
                        '_block_id'
                    ],
                    'cache_key'           => '_block_id_block',
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
            'attr'               => ['test_attr' => 'test_attr_val'],
            'label'              => 'Test Label',
            'label_attr'         => ['test_label_attr' => 'test_label_attr_val'],
            'translation_domain' => 'test_translation_domain'
        ];

        $view = $this->getBlockBuilder(BaseType::NAME, $options)
            ->getBlockView();

        $this->assertSame($view, $view->vars['block']);
        unset($view->vars['block']);

        $this->assertBlockView(
            [
                'vars' => [
                    'id'                  => 'block_id',
                    'unique_block_prefix' => '_block_id',
                    'block_prefixes'      => [
                        'block',
                        '_block_id'
                    ],
                    'cache_key'           => '_block_id_block',
                    'translation_domain'  => 'test_translation_domain',
                    'attr'                => ['test_attr' => 'test_attr_val'],
                    'label'               => 'Test Label',
                    'label_attr'          => ['test_label_attr' => 'test_label_attr_val']
                ]
            ],
            $view,
            false
        );
    }
}
