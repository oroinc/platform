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
            ->add('rootId', null, 'root');

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                  => 'rootId',
                    'translation_domain'  => 'messages',
                    'unique_block_prefix' => '_rootId',
                    'block_prefixes'      => [
                        'block',
                        'container',
                        'root',
                        '_rootId',
                    ],
                    'cache_key'           => '_rootId_root',
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
            ->add('rootId', null, 'root')
            ->add('headerId', 'rootId', 'header')
            ->add('logoId', 'headerId', 'logo', ['title' => 'test']);

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                  => 'rootId',
                    'translation_domain'  => 'messages',
                    'unique_block_prefix' => '_rootId',
                    'block_prefixes'      => [
                        'block',
                        'container',
                        'root',
                        '_rootId',
                    ],
                    'cache_key'           => '_rootId_root',
                ],
                'children' => [
                    [ // header
                        'vars'     => [
                            'id'                  => 'headerId',
                            'translation_domain'  => 'messages',
                            'unique_block_prefix' => '_rootId_headerId',
                            'block_prefixes'      => [
                                'block',
                                'container',
                                'header',
                                '_rootId_headerId',
                            ],
                            'cache_key'           => '_rootId_headerId_header',
                        ],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'                  => 'logoId',
                                    'translation_domain'  => 'messages',
                                    'unique_block_prefix' => '_rootId_headerId_logoId',
                                    'block_prefixes'      => [
                                        'block',
                                        'logo',
                                        '_rootId_headerId_logoId',
                                    ],
                                    'cache_key'           => '_rootId_headerId_logoId_logo',
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
