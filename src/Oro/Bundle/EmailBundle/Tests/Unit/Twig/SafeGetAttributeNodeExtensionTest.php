<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\EmailBundle\Twig\NodeVisitor\SafeCheckToStringNodeVisitor;
use Oro\Bundle\EmailBundle\Twig\NodeVisitor\SafeGetAttrNodeVisitor;
use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SafeGetAttributeNodeExtensionTest extends TestCase
{
    private SafeGetAttributeNodeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new SafeGetAttributeNodeExtension();
    }

    public function testGetEventDispatcherReturnsEmptyDispatcherByDefault(): void
    {
        $eventDispatcher = $this->extension->getEventDispatcher();

        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        self::assertEmpty($eventDispatcher->getListeners());
    }

    public function testSetEventDispatcherReplacesTheDefaultOne(): void
    {
        $eventDispatcher = new EventDispatcher();
        $this->extension->setEventDispatcher($eventDispatcher);

        self::assertSame($eventDispatcher, $this->extension->getEventDispatcher());
    }

    public function testGetNodeVisitorsReturnsSandboxNodeVisitors(): void
    {
        $nodeVisitors = $this->extension->getNodeVisitors();

        self::assertCount(2, $nodeVisitors);
        self::assertInstanceOf(SafeGetAttrNodeVisitor::class, $nodeVisitors[0]);
        self::assertInstanceOf(SafeCheckToStringNodeVisitor::class, $nodeVisitors[1]);
    }
}
