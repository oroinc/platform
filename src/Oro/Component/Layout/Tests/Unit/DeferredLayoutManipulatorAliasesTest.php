<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\CallbackLayoutUpdate;
use Oro\Component\Layout\Extension\PreloadedExtension;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;

/**
 * This class contains unit tests related to ALIASES
 */
class DeferredLayoutManipulatorAliasesTest extends DeferredLayoutManipulatorTestCase
{
    public function testSimpleLayoutWithAliases()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root_alias', 'header')
            ->add('logo', 'header_alias2', 'logo', ['title' => 'test'])
            ->addAlias('root_alias', 'root')
            ->addAlias('header_alias1', 'header')
            ->addAlias('header_alias2', 'header_alias1');

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

    public function testAddByAliasTwoChildren()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo1', 'header_alias1', 'logo', ['title' => 'logo1'])
            ->add('logo2', 'header_alias1', 'logo', ['title' => 'logo2'])
            ->addAlias('header_alias1', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => 'logo1']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => 'logo2']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddWithSiblingAsAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root_alias', 'header')
            ->add('logo2', 'header_alias', 'logo', [], 'logo_alias3')
            ->add('logo1', 'header_alias', 'logo', [])
            ->add('logo3', 'header_alias', 'logo', [], 'logo_alias1', true)
            ->addAlias('root_alias', 'root')
            ->addAlias('header_alias', 'header')
            ->addAlias('logo_alias1', 'logo1')
            ->addAlias('logo_alias3', 'logo3');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddWithSiblingAsUnknownAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root_alias', 'header')
            ->add('logo2', 'header_alias', 'logo', [], 'unknown_alias1')
            ->add('logo1', 'header_alias', 'logo', [])
            ->add('logo3', 'header_alias', 'logo', [], 'unknown_alias2', true)
            ->addAlias('root_alias', 'root')
            ->addAlias('header_alias', 'header')
            ->addAlias('logo_alias1', 'logo1')
            ->addAlias('logo_alias3', 'logo3');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    /** It is expected that children are added in the same order as they are registered */
    public function testAddByAliasTwoChildrenButTheFirstChildIsAddedBeforeContainer()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header_alias1', 'logo', ['title' => 'logo1'])
            ->add('header', 'root', 'header')
            ->add('logo2', 'header_alias1', 'logo', ['title' => 'logo2'])
            ->addAlias('header_alias1', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => 'logo1']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => 'logo2']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    /** It is expected that children are added in the same order as they are registered */
    public function testAddByAliasTwoChildrenButTheFirstChildAndAliasAreAddedBeforeContainer()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header_alias1', 'logo', ['title' => 'logo1'])
            ->addAlias('header_alias1', 'header')
            ->add('header', 'root', 'header')
            ->add('logo2', 'header_alias1', 'logo', ['title' => 'logo2']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => 'logo1']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => 'logo2']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddByAliasAndThenRemoveAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('header_alias1', 'header')
            ->add('logo', 'header_alias1', 'logo', ['title' => 'test'])
            ->removeAlias('header_alias1');

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

    public function testAddToRemovedAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('header_alias1', 'header')
            ->removeAlias('header_alias1')
            ->add('logo', 'header_alias1', 'logo', ['title' => 'test']);

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

    public function testRemoveNotExistAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeAlias('test_alias');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "test_alias" alias for "root" item. Reason: The "test_alias" sting cannot be used as an alias for "root" item because it is already used for "header" item.
     */
    // @codingStandardsIgnoreEnd
    public function testRedefineAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('test_alias', 'header')
            ->addAlias('test_alias', 'root');

        $this->getLayoutView();
    }

    public function testDuplicateAddAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('test_alias', 'header')
            ->addAlias('test_alias', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars' => ['id' => 'header'],
                    ]
                ]
            ],
            $view
        );
    }

    public function testMoveChildByAliasAndThenRemoveParentById()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->addAlias('header1_alias', 'header1')
            ->addAlias('header2_alias', 'header2')
            ->add('logo', 'header1_alias', 'logo', ['title' => 'logo'])
            ->move('logo', 'header2_alias');
        $this->layoutManipulator->applyChanges($this->context);
        $this->layoutManipulator
            ->remove('header1');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testMoveChildByAliasOfRemovedByIdParent()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->addAlias('header1_alias', 'header1')
            ->addAlias('header2_alias', 'header2')
            ->add('logo', 'header1_alias', 'logo', ['title' => 'logo'])
            ->remove('header1');
        $this->layoutManipulator->applyChanges($this->context);
        $this->layoutManipulator
            ->move('logo', 'header2_alias');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceItemWhenOldItemIsRemovedByAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'logo'])
            ->remove('logo_alias')
            ->addAlias('logo_alias', 'logo')
            ->add('logo', 'header', 'logo', ['title' => 'new_logo']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'new_logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceItemWhenNewItemIsAddedInAnotherBatchAndOldItemIsRemovedByAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'logo'])
            ->addAlias('logo_alias', 'logo')
            ->remove('logo_alias');
        $this->layoutManipulator->applyChanges($this->context);
        $this->layoutManipulator
            ->add('logo', 'header', 'logo', ['title' => 'new_logo']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'new_logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceItemAfterRemoveParentByAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('header_alias', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'logo'])
            ->remove('header_alias')
            ->add('logo', 'root', 'logo', ['title' => 'new_logo']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // logo
                        'vars' => ['id' => 'logo', 'title' => 'new_logo']
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetOptionByAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setOption('test_logo', 'title', 'test1')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->addAlias('test_logo', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test1']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testRemoveOptionByAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeOption('test_logo', 'title')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->addAlias('test_logo', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetOptionByRemovedAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->addAlias('test_logo', 'logo')
            ->removeAlias('test_logo')
            ->setOption('test_logo', 'title', 'test1');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test1']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testRemoveOptionByRemovedAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->addAlias('test_logo', 'logo')
            ->removeAlias('test_logo')
            ->removeOption('test_logo', 'title');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetBlockThemeByAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setBlockTheme('MyBundle:Layout:my_theme.html.twig', 'test_logo')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->addAlias('test_logo', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );

        $blockThemes = $this->rawLayoutBuilder->getRawLayout()->getBlockThemes();
        $this->assertSame(
            [
                'logo' => ['MyBundle:Layout:my_theme.html.twig']
            ],
            $blockThemes
        );
    }

    public function testLayoutUpdatesWithAliases()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', 'root_alias', 'logo');
                                $layoutManipulator->add('logo3', 'header_alias', 'logo');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo1', 'header', 'logo')
            ->addAlias('root_alias', 'root')
            ->addAlias('header_alias', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ]
                        ]
                    ],
                    [ // logo2
                        'vars' => ['id' => 'logo2', 'title' => '']
                    ]
                ]
            ],
            $view
        );
    }

    public function testLayoutItemPassedToLayoutUpdate()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header2' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add(
                                    'logo',
                                    $item->getId(),
                                    'logo',
                                    [
                                        'title' => sprintf(
                                            'id: %s, alias: %s, parentId: %s, name: %s',
                                            $item->getId(),
                                            $item->getAlias(),
                                            $item->getParentId(),
                                            $item->getTypeName()
                                        )
                                    ]
                                );
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root1', 'header')
            ->addAlias('header1', 'header')
            ->addAlias('header2', 'header1')
            ->addAlias('root1', 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => 'id: header, alias: header2, parentId: root, name: header'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testLayoutUpdatesWhenParentIsAddedByAliasInUpdate()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header'     => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', 'root_alias', 'logo');
                                $layoutManipulator->add('logo3', 'header_alias', 'logo');
                                $layoutManipulator->addAlias('header_alias', 'header');
                            }
                        )
                    ],
                    'root_alias' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('header', $item->getId(), 'header');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header', 'logo')
            ->addAlias('root_alias', 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ]
                        ]
                    ],
                    [ // logo2
                        'vars' => ['id' => 'logo2', 'title' => '']
                    ]
                ]
            ],
            $view
        );
    }
}
