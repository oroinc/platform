<?php

namespace Oro\Component\Layout\Tests\Unit;

/**
 * This class contains unit tests which are NOT RELATED to ALIASES
 */
class DeferredLayoutBuilderTest extends DeferredLayoutBuilderTestCase
{
    public function testSimpleLayout()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->add('header', 'root', 'header');

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
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->remove('header')
            ->add('header', 'root', 'header');

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testRemoveAfterAdd()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header');

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testAddToRemovedItem()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testRemoveNotExistItem()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->remove('header');

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testRemoveAlreadyRemovedItem()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->remove('header');

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
     * @expectedExceptionMessage Cannot add "logo" item to the layout. ParentItemId: root. BlockType: logo. Reason: The "logo" item already exists. Remove existing item before add the new item with the same id.
     */
    // @codingStandardsIgnoreEnd
    public function testDuplicateAdd()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->add('logo', 'root', 'logo');

        $this->layoutBuilder->applyChanges();
    }

    public function testSetOption()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->setOption('logo', 'title', 'test1')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

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
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->removeOption('logo', 'title')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

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
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header')
            ->setOption('logo', 'title', 'test1');

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }

    public function testRemoveOptionForRemovedItem()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header')
            ->removeOption('logo', 'title');

        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
            ],
            $layout->getView()
        );
    }
}
