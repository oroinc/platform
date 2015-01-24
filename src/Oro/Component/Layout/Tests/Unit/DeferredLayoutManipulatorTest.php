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
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->add('header', 'root', 'header');

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'children' => [
                    [ // header
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'title' => 'test'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $layout->getView()
        );
    }

    public function testRemoveBeforeAdd()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->remove('header')
            ->add('header', 'root', 'header');

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testRemoveAfterAdd()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header');

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testAddToRemovedItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testRemoveNotExistItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->remove('header');

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testRemoveAlreadyRemovedItem()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->remove('header');

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
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

        $this->layoutManipulator->applyChanges();
    }

    public function testSetOption()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setOption('logo', 'title', 'test1')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'children' => [
                    [ // header
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'title' => 'test1'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $layout->getView()
        );
    }

    public function testRemoveOption()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeOption('logo', 'title')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'children' => [
                    [ // header
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'title' => ''
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $layout->getView()
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

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
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

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }
}
