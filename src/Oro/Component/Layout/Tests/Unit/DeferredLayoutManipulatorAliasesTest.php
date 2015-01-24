<?php

namespace Oro\Component\Layout\Tests\Unit;

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

    public function testAddByAliasAndThenRemoveAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('header_alias1', 'header')
            ->add('logo', 'header_alias1', 'logo', ['title' => 'test'])
            ->removeAlias('header_alias1');

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

    public function testAddToRemovedAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('header_alias1', 'header')
            ->removeAlias('header_alias1')
            ->add('logo', 'header_alias1', 'logo', ['title' => 'test']);

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

    public function testRemoveNotExistAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeAlias('test_alias');

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

        $this->layoutManipulator->applyChanges();
    }

    public function testDuplicateAddAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('test_alias', 'header')
            ->addAlias('test_alias', 'header');

        $this->layoutManipulator->applyChanges();
        $layout = $this->layoutBuilder->getLayout();

        $this->assertBlockView(
            [ // root
                'children' => [
                    [ // header
                    ]
                ]
            ],
            $layout->getView()
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

    public function testRemoveOptionByAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeOption('test_logo', 'title')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->addAlias('test_logo', 'logo');

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

    public function testSetOptionByRemovedAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->addAlias('test_logo', 'logo')
            ->removeAlias('test_logo')
            ->setOption('test_logo', 'title', 'test1');

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

    public function testRemoveOptionByRemovedAlias()
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->addAlias('test_logo', 'logo')
            ->removeAlias('test_logo')
            ->removeOption('test_logo', 'title');

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
}
