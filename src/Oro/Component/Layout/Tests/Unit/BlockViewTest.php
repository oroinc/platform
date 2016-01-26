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
}
