<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\CallbackLayoutUpdate;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\PreloadedExtension;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\HeaderType;

/**
 * This class contains unit tests which are NOT RELATED to ALIASES and CHANGE COUNTERS
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DeferredLayoutManipulatorTest extends DeferredLayoutManipulatorTestCase
{
    public function testClear()
    {
        // prepare data
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header');

        // do test
        $this->layoutManipulator->clear();
        $this->layoutManipulator->applyChanges($this->context);
        $this->assertTrue($this->rawLayoutBuilder->isEmpty());
        $this->assertSame(0, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertSame(0, $this->layoutManipulator->getNumberOfRemovedItems());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\DeferredUpdateFailureException
     * @expectedExceptionMessage Failed to apply scheduled changes. 2 action(s) cannot be applied. Actions: add(header, unknown_root), add(logo, header).
     */
    // @codingStandardsIgnoreEnd
    public function testAddItemToUnknownParent()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'unknown_root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $this->getLayoutView();
    }

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

    public function testSimpleLayoutWhenSomeBlocksCreatedDirectly()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', new HeaderType())
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
     * @expectedExceptionMessage Cannot add "logo" item to the layout. ParentId: root. BlockType: logo. SiblingId: . Reason: The "logo" item already exists. Remove existing item before add the new item with the same id.
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

    public function testAddWithSibling()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo2', 'header', 'logo', [], 'logo3')
            ->add('logo1', 'header', 'logo', [])
            ->add('logo3', 'header', 'logo', [], 'logo1', true);

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

    public function testAddWithUnknownSibling()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo2', 'header', 'logo', [], 'unknown1')
            ->add('logo1', 'header', 'logo', [])
            ->add('logo3', 'header', 'logo', [], 'unknown2', true);

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

    public function testAddWithSiblingAndMoveToUnknownSiblingAfterAdd()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->add('logo1', 'header1', 'logo', [])
            ->add('logo2', 'header2', 'logo', [])
            ->add('logo3', 'header1', 'logo', [], 'logo2')
            ->move('logo2', 'header1', 'unknown', true);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars'     => ['id' => 'header1'],
                        'children' => [
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ]
                        ]
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => []
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddWithSiblingAndMoveToUnknownSiblingBeforeAdd()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->add('logo1', 'header1', 'logo', [])
            ->add('logo2', 'header2', 'logo', [])
            ->move('logo2', 'header1', 'unknown', true)
            ->add('logo3', 'header1', 'logo', [], 'logo2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars'     => ['id' => 'header1'],
                        'children' => [
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ]
                        ]
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => []
                    ]
                ]
            ],
            $view
        );
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

    public function testChangeBlockType()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->changeBlockType(
                'header',
                'logo',
                function (array $options) {
                    $options['title'] = 'test';

                    return $options;
                }
            )
            ->add('header', 'root', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header with changed block type
                        'vars'     => ['id' => 'header', 'title' => 'test']
                    ]
                ]
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
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
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
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
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
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
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
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->move('container1', 'header2', 'container2')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
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
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->move('container1', 'header2', 'unknown', true)
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
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
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
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

    public function testSetBlockTheme()
    {
        $this->layoutManipulator
            ->setBlockTheme(['MyBundle:Layout:theme1.html.twig', 'MyBundle:Layout:theme2.html.twig'])
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->setBlockTheme('MyBundle:Layout:my_theme.html.twig', 'logo')
            ->setBlockTheme('MyBundle:Layout:theme3.html.twig');

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
                'root' => [
                    'MyBundle:Layout:theme1.html.twig',
                    'MyBundle:Layout:theme2.html.twig',
                    'MyBundle:Layout:theme3.html.twig'
                ],
                'logo' => [
                    'MyBundle:Layout:my_theme.html.twig'
                ]
            ],
            $blockThemes
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\DeferredUpdateFailureException
     * @expectedExceptionMessage Failed to apply scheduled changes. 1 action(s) cannot be applied. Actions: setBlockTheme(logo).
     */
    // @codingStandardsIgnoreEnd
    public function testSetBlockThemeForUnknownItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setBlockTheme('MyBundle:Layout:my_theme.html.twig', 'logo');

        $this->getLayoutView();
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\DeferredUpdateFailureException
     * @expectedExceptionMessage Failed to apply scheduled changes. 3 action(s) cannot be applied. Actions: add(header, logo1), add(logo1, logo2), add(logo2, header).
     */
    // @codingStandardsIgnoreEnd
    public function testCyclicDependency()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'logo1', 'header')
            ->add('logo1', 'logo2', 'logo')
            ->add('logo2', 'header', 'logo');

        $this->getLayoutView();
    }

    public function testLayoutUpdates()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo1', 'header', 'logo');

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

    public function testLayoutUpdatesWhenParentIsAddedInUpdate()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                            }
                        )
                    ],
                    'root'   => [
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
            ->add('logo1', 'header', 'logo');

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

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\DeferredUpdateFailureException
     * @expectedExceptionMessage Failed to apply scheduled changes. 1 action(s) cannot be applied. Actions: add(logo1, header).
     */
    // @codingStandardsIgnoreEnd
    public function testLayoutUpdatesWhenParentIsAddedInUpdateLinkedWithChild()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'logo1' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('header', $item->getParentId(), 'header');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header', 'logo');

        $this->getLayoutView();
    }

    /**
     * test the case when removing siblingId for 'add' does not help and siblingId must be restored
     */
    public function testLayoutUpdatesWhenUpdateLinkedWithAddToUndefinedSiblingAndAddDependsToUpdate()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                                $layoutManipulator->add('logo4', $item->getId(), 'logo');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header', 'logo', [], 'logo4', true)
            ->add('header', 'root', 'header', [], 'unknown');

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
                            [ // logo4
                                'vars' => ['id' => 'logo4', 'title' => '']
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

    /**
     * test the case when removing siblingId for 'move' does not help and siblingId must be restored
     */
    public function testLayoutUpdatesWhenUpdateLinkedWithAddToUndefinedSiblingAndMoveDependsToUpdate()
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                                $layoutManipulator->add('logo4', $item->getId(), 'logo');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->move('logo1', 'header', 'logo4', true)
            ->add('logo1', 'header', 'logo', [])
            ->add('header', 'root', 'header', [], 'unknown');

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
                            [ // logo4
                                'vars' => ['id' => 'logo4', 'title' => '']
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
