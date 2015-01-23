<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class LayoutBuilderTest extends LayoutBuilderTestCase
{
    /** @var BlockTypeFactoryStub */
    protected $blockTypeFactory;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $this->blockTypeFactory = new BlockTypeFactoryStub();
        $blockTypeRegistry      = new BlockTypeRegistry($this->blockTypeFactory);
        $blockOptionsResolver   = new BlockOptionsResolver($blockTypeRegistry);

        $this->layoutBuilder = new LayoutBuilder(
            $blockTypeRegistry,
            $blockOptionsResolver
        );
    }

    public function testCoreVariablesForRootItemOnly()
    {
        $this->layoutBuilder
            ->add('test_root', null, 'root');

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                  => 'test_root',
                    'translation_domain'  => 'messages',
                    'unique_block_prefix' => '_root',
                    'block_prefixes'      => [
                        'block',
                        'root',
                        '_root',
                    ],
                    'cache_key'           => '_root',
                ],
                'children' => []
            ],
            $layout->getView(),
            false
        );
    }

    public function testCoreVariables()
    {
        $this->layoutBuilder
            ->add('test_root', null, 'root')
            ->add('test_header', 'test_root', 'header')
            ->add('test_logo', 'test_header', 'logo', ['title' => 'test']);

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                  => 'test_root',
                    'translation_domain'  => 'messages',
                    'unique_block_prefix' => '_root',
                    'block_prefixes'      => [
                        'block',
                        'root',
                        '_root',
                    ],
                    'cache_key'           => '_root',
                ],
                'children' => [
                    [ // header
                        'vars'     => [
                            'id'                  => 'test_header',
                            'translation_domain'  => 'messages',
                            'unique_block_prefix' => '_root_header',
                            'block_prefixes'      => [
                                'block',
                                'header',
                                '_root_header',
                            ],
                            'cache_key'           => '_root_header',
                        ],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'                  => 'test_logo',
                                    'translation_domain'  => 'messages',
                                    'unique_block_prefix' => '_root_header_logo',
                                    'block_prefixes'      => [
                                        'block',
                                        'logo',
                                        '_root_header_logo',
                                    ],
                                    'cache_key'           => '_root_header_logo',
                                    'title'               => 'test'
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            $layout->getView(),
            false
        );
    }
}
