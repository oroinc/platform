<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionWrapper;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ResettableExtensionWrapperTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    const EXTENSION_SERVICE_ID = 'service_id';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var ResettableExtensionWrapper */
    private $resettableExtensionWrapper;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->resettableExtensionWrapper = new ResettableExtensionWrapper(
            $this->container,
            self::EXTENSION_SERVICE_ID
        );
    }

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, ChainExtension::class);
    }

    public function testShouldImplementResettableExtensionInterface()
    {
        $this->assertClassImplements(ResettableExtensionInterface::class, ChainExtension::class);
    }

    public function testOnStart()
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects(self::exactly(2))
            ->method('onStart')
            ->with(self::identicalTo($context));

        $this->container->expects(self::once())
            ->method('get')
            ->with(self::EXTENSION_SERVICE_ID)
            ->willReturn($fooExtension);

        $this->resettableExtensionWrapper->onStart($context);

        // test that inner service is cached locally
        $this->resettableExtensionWrapper->onStart($context);
    }

    public function testOnBeforeReceive()
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects(self::exactly(2))
            ->method('onBeforeReceive')
            ->with(self::identicalTo($context));

        $this->container->expects(self::once())
            ->method('get')
            ->with(self::EXTENSION_SERVICE_ID)
            ->willReturn($fooExtension);

        $this->resettableExtensionWrapper->onBeforeReceive($context);

        // test that inner service is cached locally
        $this->resettableExtensionWrapper->onBeforeReceive($context);
    }

    public function testOnPreReceive()
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects(self::exactly(2))
            ->method('onPreReceived')
            ->with(self::identicalTo($context));

        $this->container->expects(self::once())
            ->method('get')
            ->with(self::EXTENSION_SERVICE_ID)
            ->willReturn($fooExtension);

        $this->resettableExtensionWrapper->onPreReceived($context);

        // test that inner service is cached locally
        $this->resettableExtensionWrapper->onPreReceived($context);
    }

    public function testOnPostReceive()
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects(self::exactly(2))
            ->method('onPostReceived')
            ->with(self::identicalTo($context));

        $this->container->expects(self::once())
            ->method('get')
            ->with(self::EXTENSION_SERVICE_ID)
            ->willReturn($fooExtension);

        $this->resettableExtensionWrapper->onPostReceived($context);

        // test that inner service is cached locally
        $this->resettableExtensionWrapper->onPostReceived($context);
    }

    public function testOnIdle()
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects(self::exactly(2))
            ->method('onIdle')
            ->with(self::identicalTo($context));

        $this->container->expects(self::once())
            ->method('get')
            ->with(self::EXTENSION_SERVICE_ID)
            ->willReturn($fooExtension);

        $this->resettableExtensionWrapper->onIdle($context);

        // test that inner service is cached locally
        $this->resettableExtensionWrapper->onIdle($context);
    }

    public function testOnInterrupted()
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);
        $fooExtension->expects(self::exactly(2))
            ->method('onInterrupted')
            ->with(self::identicalTo($context));

        $this->container->expects(self::once())
            ->method('get')
            ->with(self::EXTENSION_SERVICE_ID)
            ->willReturn($fooExtension);

        $this->resettableExtensionWrapper->onInterrupted($context);

        // test that inner service is cached locally
        $this->resettableExtensionWrapper->onInterrupted($context);
    }

    public function testReset()
    {
        $context = $this->createMock(Context::class);

        $fooExtension = $this->createMock(ExtensionInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with(self::EXTENSION_SERVICE_ID)
            ->willReturn($fooExtension);

        // get inner extension from the container at the first time
        $this->resettableExtensionWrapper->onStart($context);

        // test reset method
        $this->resettableExtensionWrapper->reset();
        $this->resettableExtensionWrapper->onStart($context);
    }
}
