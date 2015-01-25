<?php

namespace Oro\Component\Layout\Tests\Unit;

/**
 * This class contains unit tests which are NOT RELATED to ALIASES
 */
class DeferredLayoutManipulatorTest extends DeferredLayoutManipulatorTestCase
{
    public function testSimpleLayout()
    {
        $this->layoutManipulator
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

    public function testAddWhenRootIsAddedAtTheEnd()
    {
        $this->layoutManipulator
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->add('header', 'root', 'header')
            ->add('root', null, 'root');

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
                            ],
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddTwoChildren()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo1', 'header', 'logo', ['title' => 'logo1'])
            ->add('logo2', 'header', 'logo', ['title' => 'logo2']);

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
    public function testAddTwoChildrenButTheFirstChildIsAddedBeforeContainer()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header', 'logo', ['title' => 'logo1'])
            ->add('header', 'root', 'header')
            ->add('logo2', 'header', 'logo', ['title' => 'logo2']);

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

    public function testRemoveBeforeAdd()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->remove('header')
            ->add('header', 'root', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveAfterAdd()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testAddToRemovedItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveNotExistItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->remove('header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveAlreadyRemovedItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->remove('header');

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
     * @expectedExceptionMessage Cannot add "logo" item to the layout. ParentId: root. BlockType: logo. Reason: The "logo" item already exists. Remove existing item before add the new item with the same id.
     */
    // @codingStandardsIgnoreEnd
    public function testDuplicateAdd()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->add('logo', 'root', 'logo');

        $this->getLayoutView();
    }

    public function testSetOption()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setOption('logo', 'title', 'test1')
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
                                'vars' => ['id' => 'logo', 'title' => 'test1']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testRemoveOption()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeOption('logo', 'title')
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
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetOptionForRemovedItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header')
            ->setOption('logo', 'title', 'test1');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveOptionForRemovedItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header')
            ->removeOption('logo', 'title');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testMoveUnknownItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->move('unknown', 'root');

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

    public function testMoveToUnknownParent()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->move('logo', 'unknown');

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

    public function testMoveToParent()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', 'container')
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', 'container')
            ->add('logo2', 'container2', 'logo')
            ->move('container1', 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [ // container1
                        'vars'     => ['id' => 'container1'],
                        'children' => [
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

    public function testMoveToAnotherContainer()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', 'container')
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', 'container')
            ->add('logo2', 'container2', 'logo')
            ->move('container1', 'header2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerBeforeSibling()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', 'container')
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', 'container')
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', 'container')
            ->add('logo3', 'container3', 'logo')
            ->move('container1', 'header2', 'container3', true);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerAfterSibling()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', 'container')
            ->add('logo1', 'container1', 'logo')
            ->move('container1', 'header2', 'container2')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', 'container')
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', 'container')
            ->add('logo3', 'container3', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerBeforeUnknownSibling()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', 'container')
            ->add('logo1', 'container1', 'logo')
            ->move('container1', 'header2', 'unknown', true)
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', 'container')
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', 'container')
            ->add('logo3', 'container3', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerAfterUnknownSibling()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', 'container')
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', 'container')
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', 'container')
            ->add('logo3', 'container3', 'logo')
            ->move('container1', 'header2', 'unknown');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testLayoutChangedByBlockType()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->add('test_container', 'header', 'test_self_building_container');

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
                            ],
                            [ // test_container
                                'vars'     => ['id' => 'test_container'],
                                'children' => [
                                    [ // logo added by 'test_self_building_container' block type
                                        'vars' => ['id' => 'test_container_logo', 'title' => '']
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }
}
