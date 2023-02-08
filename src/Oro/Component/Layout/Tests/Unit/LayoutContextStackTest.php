<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutContextStack;

class LayoutContextStackTest extends \PHPUnit\Framework\TestCase
{
    private LayoutContextStack $layoutContextStack;

    protected function setUp(): void
    {
        $this->layoutContextStack = new LayoutContextStack();
    }

    public function testPushPop(): void
    {
        $context = new LayoutContext();

        $this->layoutContextStack->push($context);
        self::assertEquals($context, $this->layoutContextStack->getCurrentContext());
        self::assertEquals($context, $this->layoutContextStack->getMainContext());
        self::assertEquals(null, $this->layoutContextStack->getParentContext());
        self::assertEquals($context, $this->layoutContextStack->pop());
    }

    public function testGetMainContext(): void
    {
        $context = new LayoutContext();
        $context2 = new LayoutContext();

        $this->layoutContextStack->push($context);
        $this->layoutContextStack->push($context2);

        self::assertEquals($context2, $this->layoutContextStack->getCurrentContext());
        self::assertEquals($context, $this->layoutContextStack->getMainContext());
        self::assertEquals($context, $this->layoutContextStack->getParentContext());
        self::assertEquals($context2, $this->layoutContextStack->pop());
    }
}
