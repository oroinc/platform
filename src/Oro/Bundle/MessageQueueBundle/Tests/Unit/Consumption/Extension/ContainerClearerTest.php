<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerClearer;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

class ContainerClearerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Container|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var ContainerClearer */
    private $clearer;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);

        $this->clearer = new ContainerClearer($this->container);
    }

    public function testSetPersistentServices()
    {
        $service1 = new \stdClass();
        $serviceId1 = 'foo_service';
        $service2 = new \stdClass();
        $serviceId2 = 'bar_service';
        $this->container->expects(self::exactly(4))
            ->method('initialized')
            ->withConsecutive(
                [$serviceId1],
                [$serviceId2],
                [$serviceId1],
                [$serviceId2]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                false,
                false
            );
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [$serviceId1, Container::EXCEPTION_ON_INVALID_REFERENCE, $service1],
                [$serviceId2, Container::EXCEPTION_ON_INVALID_REFERENCE, $service2],
            ]);

        // expectations
        $this->container->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                [$serviceId1, $service1],
                [$serviceId2, $service2]
            );

        $this->clearer->setPersistentServices([$serviceId1]);
        $this->clearer->setPersistentServices([$serviceId2]);
        $this->clearer->clear($this->createMock(LoggerInterface::class));
    }

    public function testClearShouldWriteToLogAppropriateMessage()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(self::once())
            ->method('info')
            ->with('Reset the container');

        $this->clearer->clear($logger);
    }

    public function testClearShouldResetRootChainExtensionIfItImplementsResettableExtensionInterface()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootChainExtension = $this->createMock(ResettableExtensionInterface::class);

        $rootChainExtension->expects(self::once())
            ->method('reset');

        $this->clearer->setChainExtension($rootChainExtension);
        $this->clearer->clear($logger);
    }

    public function testClearShouldRemoveNonPersistentServices()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->container->expects(self::never())
            ->method('initialized');
        $this->container->expects(self::never())
            ->method('get');
        $this->container->expects(self::once())
            ->method('reset');
        $this->container->expects(self::never())
            ->method('set');

        $this->clearer->clear($logger);
    }

    public function testClearShouldRestorePersistentServiceIfItWasInitialized()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $fooService = new \stdClass();

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_service')
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_service')
            ->willReturn($fooService);
        $this->container->expects(self::once())
            ->method('reset');
        $this->container->expects(self::once())
            ->method('set')
            ->with('foo_service', self::identicalTo($fooService));

        $this->clearer->setPersistentServices(['foo_service']);
        $this->clearer->clear($logger);
    }

    public function testClearShouldNotRestorePersistentServiceIfItWasInitializedButPresentInContainer()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $fooService = new \stdClass();

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_service')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_service')
            ->willReturn($fooService);
        $this->container->expects(self::once())
            ->method('reset');
        $this->container->expects(self::never())
            ->method('set')
            ->with('foo_service', self::identicalTo($fooService));

        $logger->expects(self::once())
            ->method('notice')
            ->with('Next persistent services were already initialized during restoring: foo_service');

        $this->clearer->setPersistentServices(['foo_service']);
        $this->clearer->clear($logger);
    }

    public function testClearShouldNotRestorePersistentServiceIfItWasNotInitialized()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_service')
            ->willReturn(false);
        $this->container->expects(self::never())
            ->method('get')
            ->with('foo_service');
        $this->container->expects(self::once())
            ->method('reset');
        $this->container->expects(self::never())
            ->method('set')
            ->with('foo_service');

        $this->clearer->setPersistentServices(['foo_service']);
        $this->clearer->clear($logger);
    }
}
