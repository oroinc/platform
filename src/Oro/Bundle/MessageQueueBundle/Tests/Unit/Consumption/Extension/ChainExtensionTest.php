<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Log\ConsumerState;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChainExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testWhenConstructedShouldPassItselfToAllToAllChainExtensionAwareExtensions()
    {
        $extension1 = $this->createMock(ExtensionInterface::class);
        $extension2 = $this->createMock(ChainExtension::class);

        $passedChainExtension = null;
        $extension2->expects(self::once())
            ->method('setChainExtension')
            ->willReturnCallback(function ($extension) use (&$passedChainExtension) {
                $passedChainExtension = $extension;
            });

        $consumerState = $this->createMock(ConsumerState::class);
        $chainExtension = new ChainExtension([$extension1, $extension2], $consumerState);
        self::assertSame($chainExtension, $passedChainExtension);
    }

    public function testShouldProxyOnStartToAllInternalExtensions()
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

        $consumerState = $this->createMock(ConsumerState::class);
        $consumerState->expects($this->exactly(3))
            ->method('setExtension')
            ->withConsecutive(
                [$this->identicalTo($fooExtension)],
                [$this->identicalTo($barExtension)],
                [$this->isNull()]
            );

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onStart($context);
    }

    public function testShouldProxyOnBeforeReceiveToAllInternalExtensions()
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

        $consumerState = $this->createMock(ConsumerState::class);
        $consumerState->expects($this->exactly(3))
            ->method('setExtension')
            ->withConsecutive(
                [$this->identicalTo($fooExtension)],
                [$this->identicalTo($barExtension)],
                [$this->isNull()]
            );

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onBeforeReceive($context);
    }

    public function testShouldProxyOnPreReceiveToAllInternalExtensions()
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

        $consumerState = $this->createMock(ConsumerState::class);
        $consumerState->expects($this->exactly(3))
            ->method('setExtension')
            ->withConsecutive(
                [$this->identicalTo($fooExtension)],
                [$this->identicalTo($barExtension)],
                [$this->isNull()]
            );

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onPreReceived($context);
    }

    public function testShouldProxyOnPostReceiveToAllInternalExtensions()
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

        $consumerState = $this->createMock(ConsumerState::class);
        $consumerState->expects($this->exactly(3))
            ->method('setExtension')
            ->withConsecutive(
                [$this->identicalTo($fooExtension)],
                [$this->identicalTo($barExtension)],
                [$this->isNull()]
            );

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onPostReceived($context);
    }

    public function testShouldProxyOnIdleToAllInternalExtensions()
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

        $consumerState = $this->createMock(ConsumerState::class);
        $consumerState->expects($this->exactly(3))
            ->method('setExtension')
            ->withConsecutive(
                [$this->identicalTo($fooExtension)],
                [$this->identicalTo($barExtension)],
                [$this->isNull()]
            );

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onIdle($context);
    }

    public function testShouldProxyOnInterruptedToAllInternalExtensions()
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

        $consumerState = $this->createMock(ConsumerState::class);
        $consumerState->expects($this->exactly(3))
            ->method('setExtension')
            ->withConsecutive(
                [$this->identicalTo($fooExtension)],
                [$this->identicalTo($barExtension)],
                [$this->isNull()]
            );

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onInterrupted($context);
    }

    public function testShouldResetAllResettableExtensions()
    {
        $extension1 = $this->createMock(ExtensionInterface::class);
        $extension2 = $this->createMock(ChainExtension::class);

        $consumerState = $this->createMock(ConsumerState::class);
        $chainExtension = new ChainExtension([$extension1, $extension2], $consumerState);

        $extension2->expects(self::once())
            ->method('reset');

        $chainExtension->reset();
    }

    public function testShouldSetChainExtensionToAllToAllChainExtensionAwareExtensions()
    {
        $extension1 = $this->createMock(ExtensionInterface::class);
        $extension2 = $this->createMock(ChainExtension::class);

        $consumerState = $this->createMock(ConsumerState::class);
        $chainExtension = new ChainExtension([$extension1, $extension2], $consumerState);

        $anotherChainExtension = $this->createMock(ChainExtension::class);
        $extension2->expects(self::once())
            ->method('setChainExtension')
            ->with(self::identicalTo($anotherChainExtension));

        $chainExtension->setChainExtension($anotherChainExtension);
    }
}
