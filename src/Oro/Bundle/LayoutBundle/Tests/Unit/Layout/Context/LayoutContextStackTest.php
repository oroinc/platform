<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Context;

use Oro\Bundle\LayoutBundle\Event\LayoutContextChangedEvent;
use Oro\Bundle\LayoutBundle\Layout\Context\LayoutContextStack;
use Oro\Component\Layout\LayoutContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LayoutContextStackTest extends \PHPUnit\Framework\TestCase
{
    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private LayoutContextStack $layoutContextStack;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->layoutContextStack = new LayoutContextStack($this->eventDispatcher);
    }

    public function testPushPop(): void
    {
        $context = new LayoutContext();
        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new LayoutContextChangedEvent(null, $context)],
                [new LayoutContextChangedEvent($context, null)]
            );

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
        $this->eventDispatcher
            ->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [new LayoutContextChangedEvent(null, $context)],
                [new LayoutContextChangedEvent($context, $context2)],
                [new LayoutContextChangedEvent($context2, $context)]
            );

        $this->layoutContextStack->push($context);
        $this->layoutContextStack->push($context2);

        self::assertEquals($context2, $this->layoutContextStack->getCurrentContext());
        self::assertEquals($context, $this->layoutContextStack->getMainContext());
        self::assertEquals($context, $this->layoutContextStack->getParentContext());
        self::assertEquals($context2, $this->layoutContextStack->pop());
    }
}
