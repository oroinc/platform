<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;

class LayoutContextHolderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LayoutContextHolder
     */
    protected $layoutContextHolder;

    /**
     * @var LayoutContext
     */
    protected $context;

    protected function setUp()
    {
        $this->layoutContextHolder = new LayoutContextHolder();

        /** @var LayoutContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $this->context = $this->getMock(LayoutContext::class);
    }

    public function testContextAccessor()
    {
        $this->layoutContextHolder->setContext($this->context);

        $this->assertEquals($this->context, $this->layoutContextHolder->getContext());
    }
}
