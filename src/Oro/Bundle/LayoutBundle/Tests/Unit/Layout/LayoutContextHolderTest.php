<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\ContextInterface;

class LayoutContextHolderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutContextHolder */
    private $layoutContextHolder;

    /** @var ContextInterface */
    private $context;

    protected function setUp(): void
    {
        $this->layoutContextHolder = new LayoutContextHolder();

        $this->context = $this->createMock(ContextInterface::class);
    }

    public function testContextAccessor()
    {
        $this->layoutContextHolder->setContext($this->context);

        $this->assertEquals($this->context, $this->layoutContextHolder->getContext());
    }
}
