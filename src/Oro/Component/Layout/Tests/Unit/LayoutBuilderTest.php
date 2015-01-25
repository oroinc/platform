<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutViewFactory;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class LayoutBuilderTest extends LayoutBuilderTestCase
{
    /** @var BlockTypeFactoryStub */
    protected $blockTypeFactory;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    /** @var LayoutViewFactory */
    protected $layoutViewFactory;

    protected function setUp()
    {
        $this->layoutBuilder     = new LayoutBuilder();
        $this->blockTypeFactory  = new BlockTypeFactoryStub();
        $blockTypeRegistry       = new BlockTypeRegistry($this->blockTypeFactory);
        $blockOptionsResolver    = new BlockOptionsResolver($blockTypeRegistry);
        $this->layoutViewFactory = new LayoutViewFactory(
            $blockTypeRegistry,
            $blockOptionsResolver,
            new DeferredLayoutManipulator($this->layoutBuilder)
        );
    }

    /**
     * @param string|null $rootId
     *
     * @return BlockView
     */
    protected function getLayoutView($rootId = null)
    {
        $layoutData = $this->layoutBuilder->getLayout();

        return $this->layoutViewFactory->createView($layoutData, $rootId);
    }

    public function testSimpleLayout()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "header" item cannot be added as a child to "logo" item (block type: logo) because only container blocks can have children.
     */
    // @codingStandardsIgnoreEnd
    public function testAddChildToNotContainer()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('logo', 'root', 'logo')
            ->add('header', 'logo', 'header');

        $this->getLayoutView();
    }

    public function testCoreVariablesForRootItemOnly()
    {
        $this->layoutBuilder
            ->add('rootId', null, 'root');

        $view = $this->getLayoutView();

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
            $view,
            false
        );
    }

    public function testCoreVariables()
    {
        $this->layoutBuilder
            ->add('rootId', null, 'root')
            ->add('headerId', 'rootId', 'header')
            ->add('logoId', 'headerId', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

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
                            'unique_block_prefix' => '_headerId',
                            'block_prefixes'      => [
                                'block',
                                'container',
                                'header',
                                '_headerId',
                            ],
                            'cache_key'           => '_headerId_header',
                        ],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'                  => 'logoId',
                                    'translation_domain'  => 'messages',
                                    'unique_block_prefix' => '_logoId',
                                    'block_prefixes'      => [
                                        'block',
                                        'logo',
                                        '_logoId',
                                    ],
                                    'cache_key'           => '_logoId_logo',
                                    'title'               => 'test'
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            $view,
            false
        );
    }
}
