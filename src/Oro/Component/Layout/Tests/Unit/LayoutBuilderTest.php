<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class LayoutBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $blockTypeFactory     = new BlockTypeFactoryStub();
        $blockTypeRegistry    = new BlockTypeRegistry($blockTypeFactory);
        $blockOptionsResolver = new BlockOptionsResolver($blockTypeRegistry);

        $this->layoutBuilder = new LayoutBuilder(
            $blockTypeRegistry,
            $blockOptionsResolver
        );
    }

    public function testSimpleLayout()
    {
        $this->layoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header', [])
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $layout = $this->layoutBuilder->getLayout();

        $rootView = new BlockView();

        $headerView           = new BlockView($rootView);
        $rootView->children[] = $headerView;

        $logoView                = new BlockView($headerView);
        $headerView->children[]  = $logoView;
        $logoView->vars['title'] = 'test';

        $this->assertEquals($rootView, $layout->getView());
    }
}
