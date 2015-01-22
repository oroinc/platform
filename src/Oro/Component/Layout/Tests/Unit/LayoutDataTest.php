<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutData;

class LayoutDataTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutData */
    protected $layoutData;

    protected function setUp()
    {
        $this->layoutData = new LayoutData();
    }

    public function testGetRootItemId()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');

        // do test
        $this->assertEquals('root', $this->layoutData->getRootItemId());
    }
}
