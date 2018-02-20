<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\ContextInterface;

class LayoutContextHolderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LayoutContextHolder
     */
    protected $layoutContextHolder;

    /**
     * @var ContextInterface
     */
    protected $context;

    protected function setUp()
    {
        $this->layoutContextHolder = new LayoutContextHolder();

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $this->context = $this->createMock(ContextInterface::class);
    }

    public function testContextAccessor()
    {
        $this->layoutContextHolder->setContext($this->context);

        $this->assertEquals($this->context, $this->layoutContextHolder->getContext());
    }
}
