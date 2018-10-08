<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerClearer;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

class ContainerClearerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Container */
    private $container;

    /** @var ContainerClearer */
    private $clearer;

    protected function setUp()
    {
        $this->container = $this->createMock(Container::class);

        $this->clearer = new ContainerClearer($this->container);
    }

    public function testSetPersistentServices()
    {
        self::assertAttributeSame([], 'persistentServices', $this->clearer);

        $this->clearer->setPersistentServices(['foo_service']);
        self::assertAttributeEquals(['foo_service'], 'persistentServices', $this->clearer);

        $this->clearer->setPersistentServices(['bar_service']);
        self::assertAttributeEquals(
            ['foo_service', 'bar_service'],
            'persistentServices',
            $this->clearer
        );
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

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_service')
            ->willReturn(true);
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
