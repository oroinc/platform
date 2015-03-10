<?php

namespace Oro\Component\Layout\Tests\Unit\Util;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class BlockUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterPlugin()
    {
        $view = new BlockView();

        $view->vars['block_prefixes'] = ['block', 'container', '_my_container'];

        BlockUtils::registerPlugin($view, 'my_plugin');

        $this->assertEquals(
            ['block', 'container', 'my_plugin', '_my_container'],
            $view->vars['block_prefixes']
        );
    }
}
