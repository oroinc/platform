<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;

class BlockViewTest extends LayoutTestCase
{
    public function testConstruct()
    {
        $rootView = new BlockView();
        $this->assertArrayNotHasKey('value', $rootView->vars);
    }

    public function testGetId()
    {
        $blockView = new BlockView();
        $blockView->vars['id'] = 21;

        $this->assertEquals(21, $blockView->getId());
    }

    public function testGetIdEmpty()
    {
        $blockView = new BlockView();

        $this->assertNull($blockView->getId());
    }

    public function testGetVisible()
    {
        $blockView = new BlockView();
        $blockView->vars['visible'] = false;

        $this->assertFalse($blockView->isVisible());
    }

    public function testGetVisibleScalar()
    {
        $blockView = new BlockView();
        $blockView->vars['visible'] = 'string';

        $this->assertTrue($blockView->isVisible());
    }

    public function testGetVisibleEmpty()
    {
        $blockView = new BlockView();

        $this->assertTrue($blockView->isVisible());
    }
}
