<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class ChainExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface(): void
    {
        $this->assertClassImplements(ExtensionInterface::class, ChainExtension::class);
    }

    public function testCouldBeConstructedWithExtensionsArray(): void
    {
        new ChainExtension(
            [$this->createMock(ExtensionInterface::class), $this->createMock(ExtensionInterface::class)]
        );
    }

    public function testShouldProxyOnStartToAllInternalExtensions(): void
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context));
        $barExtension = $this->createMock(ExtensionInterface::class);
        $barExtension->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context));

        $chainExtension = new ChainExtension([$fooExtension, $barExtension]);
        $chainExtension->onStart($context);
    }

    public function testShouldProxyOnBeforeReceiveToAllInternalExtensions(): void
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context));
        $barExtension = $this->createMock(ExtensionInterface::class);
        $barExtension->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context));

        $chainExtension = new ChainExtension([$fooExtension, $barExtension]);
        $chainExtension->onBeforeReceive($context);
    }

    public function testShouldProxyOnPreReceiveToAllInternalExtensions(): void
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context));
        $barExtension = $this->createMock(ExtensionInterface::class);
        $barExtension->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context));

        $chainExtension = new ChainExtension([$fooExtension, $barExtension]);
        $chainExtension->onPreReceived($context);
    }

    public function testShouldProxyOnPostReceiveToAllInternalExtensions(): void
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context));
        $barExtension = $this->createMock(ExtensionInterface::class);
        $barExtension->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context));

        $chainExtension = new ChainExtension([$fooExtension, $barExtension]);
        $chainExtension->onPostReceived($context);
    }

    public function testShouldProxyOnIdleToAllInternalExtensions(): void
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context));
        $barExtension = $this->createMock(ExtensionInterface::class);
        $barExtension->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context));

        $chainExtension = new ChainExtension([$fooExtension, $barExtension]);
        $chainExtension->onIdle($context);
    }

    public function testShouldProxyOnInterruptedToAllInternalExtensions(): void
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context));
        $barExtension = $this->createMock(ExtensionInterface::class);
        $barExtension->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context));

        $chainExtension = new ChainExtension([$fooExtension, $barExtension]);
        $chainExtension->onInterrupted($context);
    }
}
