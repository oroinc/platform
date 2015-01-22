<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\ScheduledLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class ScheduledLayoutBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ScheduledLayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $blockTypeFactory     = new BlockTypeFactoryStub();
        $blockTypeRegistry    = new BlockTypeRegistry($blockTypeFactory);
        $blockOptionsResolver = new BlockOptionsResolver($blockTypeRegistry);

        $this->layoutBuilder = new ScheduledLayoutBuilder(
            new LayoutBuilder(
                $blockTypeRegistry,
                $blockOptionsResolver
            )
        );
    }

    public function testSimpleLayout()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
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

    public function testSimpleLayoutWithAliases()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root_alias', 'header')
            ->add('logo', 'header_alias2', 'logo', ['title' => 'test'])
            ->addAlias('root_alias', 'root')
            ->addAlias('header_alias1', 'header')
            ->addAlias('header_alias2', 'header_alias1');

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
     * @expectedExceptionMessage Cannot add "logo" item to the layout. ParentItemId: root. BlockType: logo. Error: The "logo" item already exists. Remove existing item before add the new item with the same id.
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

    /**
     * @param array     $expected
     * @param BlockView $actual
     */
    protected function assertBlockView(array $expected, BlockView $actual)
    {
        $this->completeView($expected);
        $actualArray = $this->convertBlockViewToArray($actual);
        $this->assertEquals($expected, $actualArray);
    }

    /**
     * @param array $view
     *
     * @return array
     */
    protected function completeView(array &$view)
    {
        if (!isset($view['vars'])) {
            $view['vars'] = [];
        }
        if (!isset($view['vars']['attr'])) {
            $view['vars']['attr'] = [];
        }
        if (!isset($view['vars']['value'])) {
            $view['vars']['value'] = null;
        }
        if (!isset($view['children'])) {
            $view['children'] = [];
        }
        array_walk($view['children'], [$this, 'completeView']);
    }

    /**
     * @param BlockView $view
     *
     * @return array
     */
    protected function convertBlockViewToArray(BlockView $view)
    {
        return [
            'vars'     => $view->vars,
            'children' => array_map([$this, 'convertBlockViewToArray'], $view->children)
        ];
    }
}
