<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutData;
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
     * @expectedExceptionMessage Cannot add "test" item to the layout. ParentId: root. BlockType: root. Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testAddToUnknownParent()
    {
        $this->layoutBuilder
            ->add('test', 'root', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "root" item from the layout. Reason: The "root" item does not exist.
     */
    public function testRemoveUnknownItem()
    {
        $this->layoutBuilder
            ->remove('root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "test" alias for "root" item. Reason: The "root" item does not exist.
     */
    public function testAddAliasForUnknownItem()
    {
        $this->layoutBuilder
            ->addAlias('test', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" alias. Reason: The "test" item alias does not exist.
     */
    public function testRemoveUnknownAlias()
    {
        $this->layoutBuilder
            ->removeAlias('test');
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set a value for "" option for "root" item. Reason: The option name must not be empty.
     */
    // @codingStandardsIgnoreEnd
    public function testSetOptionWithEmptyName($name)
    {
        $this->layoutBuilder
            ->setOption('root', $name, 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set a value for "test" option for "root" item. Reason: Cannot change already resolved options.
     */
    // @codingStandardsIgnoreEnd
    public function testSetOptionForAlreadyResolvedItem()
    {
        $this->layoutBuilder
            ->add('root', null, 'root');
        $this->layoutBuilder->getLayout()->setProperty('root', LayoutData::RESOLVED_OPTIONS, []);

        $this->layoutBuilder
            ->setOption('root', 'test', 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set a value for "test" option for "root" item. Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testSetOptionForUnknownItem()
    {
        $this->layoutBuilder
            ->setOption('root', 'test', 123);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "" option for "root" item. Reason: The option name must not be empty.
     */
    public function testRemoveOptionWithEmptyName($name)
    {
        $this->layoutBuilder
            ->removeOption('root', $name);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" option for "root" item. Reason: Cannot change already resolved options.
     */
    // @codingStandardsIgnoreEnd
    public function testRemoveOptionForAlreadyResolvedItem()
    {
        $this->layoutBuilder
            ->add('root', null, 'root');
        $this->layoutBuilder->getLayout()->setProperty('root', LayoutData::RESOLVED_OPTIONS, []);

        $this->layoutBuilder
            ->removeOption('root', 'test');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" option for "root" item. Reason: The "root" item does not exist.
     */
    public function testRemoveOptionForUnknownItem()
    {
        $this->layoutBuilder
            ->removeOption('root', 'test');
    }

    public function testGetOptions()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('logo', 'root', 'logo', ['title' => 'test']);

        $this->assertSame(
            [],
            $this->layoutBuilder->getOptions('root')
        );
        $this->assertSame(
            ['title' => 'test'],
            $this->layoutBuilder->getOptions('logo')
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot get options for "root" item. Reason: The "root" item does not exist.
     */
    public function testGetOptionsForUnknownItem()
    {
        $this->layoutBuilder
            ->getOptions('root');
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

    public function emptyStringDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }
}
