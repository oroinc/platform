<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\Testing\ClassExtensionTrait;

class ExtensionsTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, Extensions::class);
    }

    public function testCouldBeConstructedWithExtensionsArray()
    {
        new Extensions([$this->createExtension(), $this->createExtension()]);
    }

    public function testShouldProxyOnStartToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context))
        ;

        $extensions = new Extensions([$fooExtension, $barExtension]);

        $extensions->onStart($context);
    }

    public function testShouldProxyOnBeforeReceiveToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context))
        ;

        $extensions = new Extensions([$fooExtension, $barExtension]);

        $extensions->onBeforeReceive($context);
    }

    public function testShouldProxyOnPreReceiveToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context))
        ;

        $extensions = new Extensions([$fooExtension, $barExtension]);

        $extensions->onPreReceived($context);
    }

    public function testShouldProxyOnPostReceiveToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context))
        ;

        $extensions = new Extensions([$fooExtension, $barExtension]);

        $extensions->onPostReceived($context);
    }

    public function testShouldProxyOnIdleToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context))
        ;

        $extensions = new Extensions([$fooExtension, $barExtension]);

        $extensions->onIdle($context);
    }

    public function testShouldProxyOnInterruptedToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context))
        ;

        $extensions = new Extensions([$fooExtension, $barExtension]);

        $extensions->onInterrupted($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextMock()
    {
        return $this->getMock(Context::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->getMock(ExtensionInterface::class);
    }
}
