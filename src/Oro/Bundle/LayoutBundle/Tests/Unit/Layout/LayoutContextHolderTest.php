<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\ContextInterface;
use PHPUnit\Framework\TestCase;

class LayoutContextHolderTest extends TestCase
{
    private LayoutContextHolder $layoutContextHolder;
    private ContextInterface $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->layoutContextHolder = new LayoutContextHolder();

        $this->context = $this->createMock(ContextInterface::class);
    }

    public function testContextAccessor(): void
    {
        $this->layoutContextHolder->setContext($this->context);

        $this->assertEquals($this->context, $this->layoutContextHolder->getContext());
    }
}
