<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockFactory;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\ExtensionManager;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\PreloadedExtension;
use Oro\Component\Layout\RawLayout;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

class RawLayoutBuilderTest extends LayoutTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    /** @var BlockFactory */
    protected $blockFactory;

    protected function setUp()
    {
        $extensionManager = new ExtensionManager();
        $extensionManager->addExtension(
            new PreloadedExtension(
                [
                    'root'                         => new Type\RootType(),
                    'header'                       => new Type\HeaderType(),
                    'logo'                         => new Type\LogoType(),
                    'test_self_building_container' => new Type\TestSelfBuildingContainerType()
                ]
            )
        );

        $this->context          = new LayoutContext();
        $this->rawLayoutBuilder = new RawLayoutBuilder();
        $this->blockFactory     = new BlockFactory(
            $extensionManager,
            new DeferredLayoutManipulator($this->rawLayoutBuilder, $extensionManager)
        );
    }

    /**
     * @param string|null $rootId
     *
     * @return BlockView
     */
    protected function getLayoutView($rootId = null)
    {
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();

        return $this->blockFactory->createBlockView($rawLayout, $this->context, $rootId);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The root item does not exist.
     */
    public function testClear()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');

        $this->rawLayoutBuilder->clear();
        $this->getLayoutView();
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->rawLayoutBuilder->isEmpty());

        $this->rawLayoutBuilder
            ->add('root', null, 'root');

        $this->assertFalse($this->rawLayoutBuilder->isEmpty());
    }

    /**
     * @dataProvider isParentForDataProvider
     */
    public function testIsParentFor($expected, $parentId, $id)
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->addAlias('root_alias', 'root')
            ->addAlias('header_alias', 'header')
            ->addAlias('logo_alias', 'logo');

        $this->assertEquals($expected, $this->rawLayoutBuilder->isParentFor($parentId, $id));
    }

    public function isParentForDataProvider()
    {
        return [
            [true, 'header', 'logo'],
            [true, 'header_alias', 'logo_alias'],
            [false, 'root', 'logo'],
            [false, 'unknown', 'logo'],
            [false, 'header', 'unknown'],
            [false, 'unknown', 'unknown']
        ];
    }

    public function testSimpleLayout()
    {
        $this->rawLayoutBuilder
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
     * @expectedExceptionMessage Cannot add "test" item to the layout. ParentId: root. BlockType: root. SiblingId: . Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testAddToUnknownParent()
    {
        $this->rawLayoutBuilder
            ->add('test', 'root', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "root" item from the layout. Reason: The "root" item does not exist.
     */
    public function testRemoveUnknownItem()
    {
        $this->rawLayoutBuilder
            ->remove('root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot move "root" item. ParentId: destination. SiblingId: . Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testMoveUnknownItem()
    {
        $this->rawLayoutBuilder
            ->move('root', 'destination');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "test" alias for "root" item. Reason: The "root" item does not exist.
     */
    public function testAddAliasForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->addAlias('test', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" alias. Reason: The "test" item alias does not exist.
     */
    public function testRemoveUnknownAlias()
    {
        $this->rawLayoutBuilder
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
        $this->rawLayoutBuilder
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
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
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
        $this->rawLayoutBuilder
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
        $this->rawLayoutBuilder
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
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
            ->removeOption('root', 'test');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" option for "root" item. Reason: The "root" item does not exist.
     */
    public function testRemoveOptionForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->removeOption('root', 'test');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set theme(s) for "root" item. Reason: The "root" item does not exist.
     */
    public function testSetBlockThemeForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->setBlockTheme('MyBundle:Layout:my_theme.html.twig', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set theme(s) for "" item. Reason: The root item does not exist.
     */
    public function testSetRootBlockThemeForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->setBlockTheme('MyBundle:Layout:my_theme.html.twig');
    }

    public function testGetOptions()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('logo', 'root', 'logo', ['title' => 'test']);

        $this->assertSame(
            [],
            $this->rawLayoutBuilder->getOptions('root')
        );
        $this->assertSame(
            ['title' => 'test'],
            $this->rawLayoutBuilder->getOptions('logo')
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot get options for "root" item. Reason: The "root" item does not exist.
     */
    public function testGetOptionsForUnknownItem()
    {
        $this->rawLayoutBuilder
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
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('logo', 'root', 'logo')
            ->add('header', 'logo', 'header');

        $this->getLayoutView();
    }

    public function testCoreVariablesForRootItemOnly()
    {
        $this->rawLayoutBuilder
            ->add('rootId', null, 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                  => 'rootId',
                    'translation_domain'  => 'messages',
                    'unique_block_prefix' => '_rootId',
                    'block_prefixes'      => [
                        BaseType::NAME,
                        ContainerType::NAME,
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
        $this->rawLayoutBuilder
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
                        BaseType::NAME,
                        ContainerType::NAME,
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
                                BaseType::NAME,
                                ContainerType::NAME,
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
                                        BaseType::NAME,
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
